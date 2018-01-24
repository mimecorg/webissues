<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2017 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Issue_Edit
{
    public function run( $arguments )
    {
        $principal = System_Api_Principal::getCurrent();
        $principal->checkAuthenticated();

        $mode = isset( $arguments[ 'mode' ] ) ? $arguments[ 'mode' ] : null;
        $issueId = isset( $arguments[ 'issueId' ] ) ? (int)$arguments[ 'issueId' ] : null;
        $folderId = isset( $arguments[ 'folderId' ] ) ? (int)$arguments[ 'folderId' ] : null;
        $name = isset( $arguments[ 'name' ] ) ? $arguments[ 'name' ] : null;
        $description = isset( $arguments[ 'description' ] ) ? $arguments[ 'description' ] : null;
        $descriptionFormat = isset( $arguments[ 'descriptionFormat' ] ) ? (int)$arguments[ 'descriptionFormat' ] : null;

        switch ( $mode ) {
            case 'add':
                if ( $issueId != null || $folderId == null || $name == null )
                    throw new Server_Error( Server_Error::InvalidArguments );
                break;
            case 'edit':
                if ( $issueId == null || $folderId != null || $description != null )
                    throw new Server_Error( Server_Error::InvalidArguments );
                break;
            case 'clone':
                if ( $issueId == null || $folderId == null || $name == null )
                    throw new Server_Error( Server_Error::InvalidArguments );
                break;
            default:
                throw new Server_Error( Server_Error::InvalidArguments );
        }

        $values = array();

        if ( isset( $arguments[ 'values' ] ) ) {
            if ( !is_array( $arguments[ 'values' ] ) )
                throw new Server_Error( Server_Error::InvalidArguments );

            foreach ( $arguments[ 'values' ] as $item ) {
                $id = isset( $item[ 'id' ] ) ? (int)$item[ 'id' ] : null;
                $value = isset( $item[ 'value' ] ) ? $item[ 'value' ] : null;

                if ( $id == null )
                    throw new Server_Error( Server_Error::InvalidArguments );

                $values[ $id ] = $value;
            }
        }

        $issueManager = new System_Api_IssueManager();

        if ( $issueId != null )
            $issue = $issueManager->getIssue( $issueId );
        else
            $issue = null;

        if ( $folderId != null ) {
            $projectManager = new System_Api_ProjectManager();
            $folder = $projectManager->getFolder( $folderId );
            if ( $issue != null && $issue[ 'type_id' ] != $folder[ 'type_id' ] )
                throw new System_Api_Error( System_Api_Error::UnknownFolder );
        } else {
            $folder = null;
        }

        $parser = new System_Api_Parser();
        if ( $folder != null )
            $parser->setProjectId( $folder[ 'project_id' ] );
        else
            $parser->setProjectId( $issue[ 'project_id' ] );

        if ( $name != null )
            $name = $parser->normalizeString( $name, System_Const::ValueMaxLength );

        if ( $description != null ) {
            $serverManager = new System_Api_ServerManager();
            $description = $parser->normalizeString( $description, $serverManager->getSetting( 'comment_max_length' ), System_Api_Parser::AllowEmpty | System_Api_Parser::MultiLine );
            $parser->checkTextFormat( $descriptionFormat );
        }

        $typeManager = new System_Api_TypeManager();

        if ( $issue != null ) {
            $type = $typeManager->getIssueTypeForIssue( $issue );
            $rows = $issueManager->getAllAttributeValuesForIssue( $issue );
        } else {
            $type = $typeManager->getIssueTypeForFolder( $folder );
            $rows = $typeManager->getAttributeTypesForIssueType( $type );
        }

        $attributes = array();
        foreach ( $rows as $row )
            $attributes[ $row[ 'attr_id' ] ] = $row;

        if ( $issue == null || $mode == 'clone' ) {
            $initialValues = array();
            foreach ( $attributes as $id => $attribute ) {
                $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
                $initialValue = $info->getMetadata( 'default', '' );
                $initialValues[ $id ] = $typeManager->convertInitialValue( $info, $initialValue );
            }
        }

        if ( $issue == null ) {
            $oldValues = $initialValues;
        } else {
            $oldValues = array();
            foreach ( $rows as $row )
                $oldValues[ $row[ 'attr_id' ] ] = $row[ 'attr_value' ];
        }

        foreach ( $values as $id => &$value ) {
            if ( !isset( $attributes[ $id ] ) )
                throw new System_Api_Error( System_Api_Error::UnknownAttribute );

            $attribute = $attributes[ $id ];
            $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );

            $flags = System_Api_Parser::AllowEmpty;
            if ( $info->getType() == 'TEXT' && $info->getMetadata( 'multi-line', 0 ) )
                $flags |= System_Api_Parser::MultiLine;
            $value = $parser->normalizeString( $value, System_Const::ValueMaxLength, $flags );

            $value = $parser->convertAttributeValue( $attribute[ 'attr_def' ], $value );
        }

        foreach ( $oldValues as $id => $oldValue ) {
            if ( !isset( $values[ $id ] ) ) {
                $attribute = $attributes[ $id ];
                $parser->checkAttributeValue( $attribute[ 'attr_def' ], $oldValue );

                if ( $mode == 'clone' && $oldValue != $initialValues[ $id ] )
                    $values[ $id ] = $oldValue;
            }
        }

        $orderedValues = array();
        foreach ( $values as $id => $newValue ) {
            $row = array();
            $row[ 'attr_id' ] = $id;
            $row[ 'attr_value' ] = $newValue;
            $orderedValues[] = $row;
        }

        if ( !empty( $orderedValues ) ) {
            $viewManager = new System_Api_ViewManager();
            $orderedValues = $viewManager->sortByAttributeOrder( $type, $orderedValues );
        }

        $lastStampId = null;

        if ( $issue == null || $mode == 'clone' ) {
            $issueId = $issueManager->addIssue( $folder, $name, $initialValues );
            $lastStampId = $issueId;

            $issue = $issueManager->getIssue( $issueId );

            if ( $description != '' )
                $lastStampId = $issueManager->addDescription( $issue, $description, $descriptionFormat );
        } else {
            if ( $name != null ) {
                $stampId = $issueManager->renameIssue( $issue, $name );
                if ( $stampId != false )
                    $lastStampId = $stampId;
            }
        }

        foreach ( $orderedValues as $row ) {
            $stampId = $issueManager->setValue( $issue, $attributes[ $row[ 'attr_id' ] ], $row[ 'attr_value' ] );
            if ( $stampId != false )
                $lastStampId = $stampId;
        }

        $result[ 'issueId' ] = $issueId;
        $result[ 'stampId' ] = $lastStampId;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issue_Edit' );
