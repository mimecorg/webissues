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

        $issueId = isset( $arguments[ 'issueId' ] ) ? (int)$arguments[ 'issueId' ] : null;
        $name = isset( $arguments[ 'name' ] ) ? $arguments[ 'name' ] : null;

        if ( $issueId == null )
            throw new Server_Error( Server_Error::InvalidArguments );

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
        $issue = $issueManager->getIssue( $issueId );

        $parser = new System_Api_Parser();

        if ( $name != null )
            $name = $parser->normalizeString( $name, System_Const::ValueMaxLength );

        $attributes = array();

        if ( !empty( $values ) ) {
            $rows = $issueManager->getAllAttributeValuesForIssue( $issue );

            foreach ( $values as $id => &$value ) {
                $attribute = null;
                foreach ( $rows as $row ) {
                    if ( $row[ 'attr_id' ] == $id ) {
                        $attribute = $row;
                        break;
                    }
                }

                if ( $attribute == null )
                    throw new Server_Error( Server_Error::UnknownAttribute );

                $attributes[ $id ] = $attribute;

                $value = $parser->normalizeString( $value, System_Const::ValueMaxLength, System_Api_Parser::AllowEmpty );
                $value = $parser->convertAttributeValue( $attribute[ 'attr_def' ], $value );
            }
        }

        $result = false;

        if ( $name != null ) {
            $stampId = $issueManager->renameIssue( $issue, $name );
            if ( $stampId != false )
                $result = $stampId;
        }

        if ( !empty( $values ) ) {
            foreach ( $values as $id => $newValue ) {
                $stampId = $issueManager->setValue( $issue, $attributes[ $id ], $newValue );
                if ( $stampId != false )
                    $result = $stampId;
            }
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issue_Edit' );
