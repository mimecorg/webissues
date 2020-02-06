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

class Server_Api_Alerts_List
{
    public $access = '*';

    public $params = array(
        'publicAlerts' => array( 'type' => 'bool', 'default' => false ),
        'personalAlerts' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $publicAlerts, $personalAlerts )
    {
        if ( $publicAlerts ) {
            $alertManager = new System_Api_AlertManager();
            $alertRows = $alertManager->getPublicAlerts();

            $result[ 'publicAlerts' ] = array();

            foreach ( $alertRows as $alert )
                $result[ 'publicAlerts' ][] = $this->processAlert( $alert );
        }

        if ( $personalAlerts ) {
            $alertManager = new System_Api_AlertManager();
            $alertRows = $alertManager->getPersonalAlerts();

            $result[ 'personalAlerts' ] = array();

            foreach ( $alertRows as $alert )
                $result[ 'personalAlerts' ][] = $this->processAlert( $alert );
        }

        return $result;
    }

    private function processAlert( $alert )
    {
        $resultAlert = array();
        $resultAlert[ 'id' ] = $alert[ 'alert_id' ];
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
        return $resultAlert;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Alerts_List' );
