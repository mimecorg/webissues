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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Alerts_Status
{
    public $access = '*';

    public $params = array();

    public function run()
    {
        $alertManager = new System_Api_AlertManager();
        $alertRows = $alertManager->getAlerts();

        $result[ 'publicAlerts' ] = array();
        $result[ 'personalAlerts' ] = array();

        foreach ( $alertRows as $alert ) {
            $resultAlert = $this->processAlert( $alert );
            if ( $resultAlert != null ) {
                if ( $alert[ 'is_public' ] == 1 )
                    $result[ 'publicAlerts' ][] = $resultAlert;
                else
                    $result[ 'personalAlerts' ][] = $resultAlert;
            }
        }

        return $result;
    }

    private function processAlert( $alert )
    {
        $alertManager = new System_Api_AlertManager();

        $type = $alertManager->getIssueTypeFromAlert( $alert );
        $view = $alertManager->getViewFromAlert( $alert );
        $project = $alertManager->getProjectFromAlert( $alert );
        $folder = $alertManager->getFolderFromAlert( $alert );

        $queryGenerator = new System_Api_QueryGenerator();

        $queryGenerator->setIssueType( $type );

        if ( $folder != null )
            $queryGenerator->setFolder( $folder );
        else if ( $project != null )
            $queryGenerator->setProject( $project );

        if ( $view != null ) {
            $definition = $view[ 'view_def' ];
        } else {
            $viewManager = new System_Api_ViewManager();
            $definition = $viewManager->getViewSetting( $type, 'default_view' );
        }

        if ( $definition != null )
            $queryGenerator->setViewDefinition( $definition );

        $queryGenerator->setNoRead( true );

        $connection = System_Core_Application::getInstance()->getConnection();

        $query = $queryGenerator->generateCountQuery();
        $count = $connection->queryScalarArgs( $query, $queryGenerator->getQueryArguments() );

        if ( $count == 0 )
            return null;

        $resultAlert = array();
        $resultAlert[ 'id' ] = $alert[ 'alert_id' ];
        $resultAlert[ 'typeId' ] = $alert[ 'type_id' ];
        $resultAlert[ 'viewId' ] = $alert[ 'view_id' ];
        $resultAlert[ 'projectId' ] = $alert[ 'project_id' ];
        $resultAlert[ 'folderId' ] = $alert[ 'folder_id' ];
        if ( $alert[ 'view_name' ] != null )
            $resultAlert[ 'view' ] = $alert[ 'type_name' ] . " \xE2\x80\x94 " . $alert[ 'view_name' ];
        else
            $resultAlert[ 'view' ] = $alert[ 'type_name' ];
        if ( $alert[ 'folder_name' ] != null && $alert[ 'project_name' ] != null )
            $resultAlert[ 'location' ] = $alert[ 'project_name' ] . " \xE2\x80\x94 " . $alert[ 'folder_name' ];
        else if ( $alert[ 'project_name' ] != null )
            $resultAlert[ 'location' ] = $alert[ 'project_name' ];
        else
            $resultAlert[ 'location' ] = null;
        $resultAlert[ 'count' ] = $count;
        return $resultAlert;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Alerts_Status' );
