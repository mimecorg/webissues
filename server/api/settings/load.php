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

class Server_Api_Settings_Load
{
    public $access = 'admin';

    public $params = array(
        'inboxes' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $inboxes )
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $result[ 'serverName' ] = $server[ 'server_name' ];

        $engine = $serverManager->getSetting( 'email_engine' );
        $settings[ 'emailEngine' ] = $engine;

        if ( $engine != null )
            $settings[ 'emailFrom' ] = $serverManager->getSetting( 'email_from' );

        $cronLast = $serverManager->getSetting( 'cron_last' );
        if ( $cronLast != null )
            $settings[ 'cronLast' ] = max( time() - $cronLast, 0 );

        $settings[ 'anonymousAccess' ] = $serverManager->getSetting( 'anonymous_access' ) == 1;
        if ( $engine != null )
            $settings[ 'selfRegister' ] = $serverManager->getSetting( 'self_register' ) == 1;

        $settings[ 'language' ] = $serverManager->getSetting( 'language' );

        $timeZone = $serverManager->getSetting( 'time_zone' );
        if ( $timeZone == null ) {
            $date = new DateTime();
            $timeZone = $date->getTimezone()->getName();
        }
        $settings[ 'timeZone' ] = $timeZone;

        $result[ 'settings' ] = $settings;

        if ( $inboxes ) {
            $inboxManager = new System_Api_InboxManager();
            $inboxRows = $inboxManager->getInboxes();

            $result[ 'inboxes' ] = array();

            foreach ( $inboxRows as $inbox ) {
                $resultInbox = array();
                $resultInbox[ 'id' ] = $inbox[ 'inbox_id' ];
                $resultInbox[ 'engine' ] = $inbox[ 'inbox_engine' ];
                $resultInbox[ 'email' ] = $inbox[ 'inbox_email' ];
                $result[ 'inboxes' ][] = $resultInbox;
            }
        }

        $result[ 'hasImap' ] = function_exists( 'imap_open' );

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Load' );
