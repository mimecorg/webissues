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

class Server_Api_Issues_Add
{
    public function run( $arguments )
    {
        $principal = System_Api_Principal::getCurrent();
        $principal->checkAuthenticated();

        $folderId = isset( $arguments[ 'folderId' ] ) ? (int)$arguments[ 'folderId' ] : null;
        $name = isset( $arguments[ 'name' ] ) ? $arguments[ 'name' ] : null;
        $description = isset( $arguments[ 'description' ] ) ? $arguments[ 'description' ] : null;
        $descriptionFormat = isset( $arguments[ 'descriptionFormat' ] ) ? (int)$arguments[ 'descriptionFormat' ] : null;

        if ( $folderId == null || $name == null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $helper = new Server_Api_Issues_Helper();
        $values = $helper->getValues( $arguments );

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId );

        $parser = new System_Api_Parser();
        $parser->setProjectId( $folder[ 'project_id' ] );

        $name = $parser->normalizeString( $name, System_Const::ValueMaxLength );

        if ( $description != null ) {
            $serverManager = new System_Api_ServerManager();
            $description = $parser->normalizeString( $description, $serverManager->getSetting( 'comment_max_length' ), System_Api_Parser::AllowEmpty | System_Api_Parser::MultiLine );
            $parser->checkTextFormat( $descriptionFormat );
        }

        $typeManager = new System_Api_TypeManager();

        $type = $typeManager->getIssueTypeForFolder( $folder );
        $rows = $typeManager->getAttributeTypesForIssueType( $type );

        $attributes = array();
        foreach ( $rows as $row )
            $attributes[ $row[ 'attr_id' ] ] = $row;

        $initialValues = $helper->getInitialValues( $attributes, $typeManager );

        $oldValues = $initialValues;

        $values = $helper->convertValues( $values, $attributes, $parser );

        foreach ( $oldValues as $id => $oldValue ) {
            if ( !isset( $values[ $id ] ) ) {
                $attribute = $attributes[ $id ];
                $parser->checkAttributeValue( $attribute[ 'attr_def' ], $oldValue );
            }
        }

        $orderedValues = $helper->getOrderedValues( $values, $type );

        $issueManager = new System_Api_IssueManager();

        $issueId = $issueManager->addIssue( $folder, $name, $initialValues );
        $lastStampId = $issueId;

        $issue = $issueManager->getIssue( $issueId );

        if ( $description != null )
            $lastStampId = $issueManager->addDescription( $issue, $description, $descriptionFormat );

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

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_Add' );
