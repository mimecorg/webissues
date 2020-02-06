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

class Server_Api_Settings_Access_Load
{
    public $access = 'admin';

    public $params = array();

    public function run()
    {
        $serverManager = new System_Api_ServerManager();

        $engine = $serverManager->getSetting( 'email_engine' );
        $settings[ 'emailEngine' ] = $engine;

        $settings[ 'anonymousAccess' ] = $serverManager->getSetting( 'anonymous_access' ) == 1;
        if ( $engine != null ) {
            $selfRegister = $serverManager->getSetting( 'self_register' );
            $settings[ 'selfRegister' ] = $selfRegister == 1;

            if ( $selfRegister == 1 ) {
                $registerAutoApprove = $serverManager->getSetting( 'register_auto_approve' );
                $settings[ 'registerAutoApprove' ] = $registerAutoApprove == 1;
                if ( $registerAutoApprove != 1 )
                    $settings[ 'registerNotifyEmail' ] = $serverManager->getSetting( 'register_notify_email' );
            }
        }

        $result[ 'settings' ] = $settings;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Access_Load' );
