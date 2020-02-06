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

class Server_Api_Types_Views_Load
{
    public $access = '*';

    public $params = array(
        'typeId' => array( 'type' => 'int', 'required' => true ),
        'viewId' => array( 'type' => 'int', 'required' => true ),
        'details' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $typeId, $viewId, $details )
    {
        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );

        $viewManager = new System_Api_ViewManager();
        $view = $viewManager->getViewForIssueType( $type, $viewId, System_Api_ViewManager::AllowEdit );

        $result[ 'id' ] = $view[ 'view_id' ];
        $result[ 'name' ] = $view[ 'view_name' ];
        $result[ 'isPublic' ] = $view[ 'is_public' ] != 0;

        if ( $details ) {
            $resultDetails = array();

            $helper = new Server_Api_Helpers_Views();
            $helper->getViewInformation( $resultDetails, $view[ 'view_def' ] );
            $helper->getViewFilters( $resultDetails, $view[ 'view_def' ] );

            $result[ 'details' ] = $resultDetails;
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Types_Views_Load' );
