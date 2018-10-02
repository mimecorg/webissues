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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Types_Views_Add
{
    public $access = 'admin';

    public $params = array(
        'typeId' => array( 'type' => 'int', 'required' => true ),
        'name' => array( 'type' => 'string', 'required' => true ),
        'columns' => array( 'type' => 'string', 'required' => true ),
        'sortColumn' => array( 'type' => 'int', 'required' => true ),
        'sortAscending' => array( 'type' => 'bool', 'required' => true ),
        'filters' => array( 'type' => 'array', 'required' => true )
    );

    public function run( $typeId, $name, $columns, $sortColumn, $sortAscending, $filters )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $name, System_Const::NameMaxLength );

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $attributes = $typeManager->getAttributeTypesForIssueType( $type );

        $info = new System_Api_DefinitionInfo();
        $info->setType( 'VIEW' );
        $info->setMetadata( 'columns', $columns );
        $info->setMetadata( 'sort-column', $sortColumn );
        if ( $sortAscending == false )
            $info->setMetadata( 'sort-desc', 1 );

        if ( !empty( $filters ) ) {
            $conditions = array();

            foreach ( $filters as $filter ) {
                if ( !is_array( $filter ) || !isset( $filter[ 'operator' ] ) || !is_string( $filter[ 'operator' ] )
                     || !isset( $filter[ 'column' ] ) || !is_integer( $filter[ 'column' ] ) || !isset( $filter[ 'value' ] ) || !is_string( $filter[ 'value' ] ) )
                    throw new Server_Error( Server_Error::InvalidArguments );

                $condition = new System_Api_DefinitionInfo();
                $condition->setType( $filter[ 'operator' ] );
                $condition->setMetadata( 'column', $filter[ 'column' ] );
                $condition->setMetadata( 'value', $filter[ 'value' ] );

                $conditions[] = $condition->toString();
            }

            $info->setMetadata( 'filters', $conditions );
        }

        $definition = $info->toString();

        $validator->checkViewDefinition( $attributes, $definition );

        $viewManager = new System_Api_ViewManager();

        $result[ 'viewId' ] = $viewManager->addPublicView( $type, $name, $definition );
        $result[ 'changed' ] = true;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Types_Views_Add' );
