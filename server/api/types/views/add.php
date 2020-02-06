<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
    public $access = '*';

    public $params = array(
        'typeId' => array( 'type' => 'int', 'required' => true ),
        'isPublic' => array( 'type' => 'bool', 'required' => true ),
        'name' => array( 'type' => 'string', 'required' => true ),
        'columns' => array( 'type' => 'string', 'required' => true ),
        'sortColumn' => array( 'type' => 'int', 'required' => true ),
        'sortAscending' => array( 'type' => 'bool', 'required' => true ),
        'filters' => array( 'type' => 'array', 'required' => true )
    );

    public function run( $typeId, $isPublic, $name, $columns, $sortColumn, $sortAscending, $filters )
    {
        if ( $isPublic && !System_Api_Principal::getCurrent()->isAdministrator() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $validator = new System_Api_Validator();
        $validator->checkString( $name, System_Const::NameMaxLength );

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $attributes = $typeManager->getAttributeTypesForIssueType( $type );

        $helper = new Server_Api_Helpers_Views();
        $definition = $helper->createViewDefinition( $columns, $sortColumn, $sortAscending, $filters );

        $validator->checkViewDefinition( $attributes, $definition );

        $viewManager = new System_Api_ViewManager();

        if ( $isPublic )
            $result[ 'viewId' ] = $viewManager->addPublicView( $type, $name, $definition );
        else
            $result[ 'viewId' ] = $viewManager->addPersonalView( $type, $name, $definition );
        $result[ 'changed' ] = true;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Types_Views_Add' );
