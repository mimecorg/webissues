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

class Server_Api_Alerts_Load
{
    public $access = '*';

    public $params = array(
        'alertId' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $alertId )
    {
        $alertManager = new System_Api_AlertManager();
        $alert = $alertManager->getAlert( $alertId, System_Api_AlertManager::AllowEdit );

        $result[ 'id' ] = $alert[ 'alert_id' ];
        $result[ 'isPublic' ] = $alert[ 'is_public' ] != 0;

        if ( $alert[ 'view_name' ] != null )
            $result[ 'view' ] = $alert[ 'type_name' ] . " \xE2\x80\x94 " . $alert[ 'view_name' ];
        else
            $result[ 'view' ] = $alert[ 'type_name' ];
        if ( $alert[ 'folder_name' ] != null && $alert[ 'project_name' ] != null )
            $result[ 'location' ] = $alert[ 'project_name' ] . " \xE2\x80\x94 " . $alert[ 'folder_name' ];
        else if ( $alert[ 'project_name' ] != null )
            $result[ 'location' ] = $alert[ 'project_name' ];
        else
            $result[ 'location' ] = null;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Alerts_Load' );
