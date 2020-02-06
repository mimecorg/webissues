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

class Server_Api_Settings_Inboxes_Load
{
    public $access = 'admin';

    public $params = array(
        'inboxId' => array( 'type' => 'int', 'required' => true ),
        'details' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $inboxId, $details )
    {
        if ( !function_exists( 'imap_open' ) )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $serverManager = new System_Api_ServerManager();
        $engine = $serverManager->getSetting( 'email_engine' );

        $inboxManager = new System_Api_InboxManager();
        $inbox = $inboxManager->getInbox( $inboxId );

        $result[ 'engine' ] = $inbox[ 'inbox_engine' ];
        $result[ 'email' ] = $inbox[ 'inbox_email' ];

        if ( $details ) {
            $resultDetails[ 'server' ] = $inbox[ 'inbox_server' ];
            $resultDetails[ 'port' ] = $inbox[ 'inbox_port' ];
            $resultDetails[ 'encryption' ] = $inbox[ 'inbox_encryption' ];
            $resultDetails[ 'user' ] = $inbox[ 'inbox_user' ];
            $resultDetails[ 'password' ] = $inbox[ 'inbox_password' ];
            $resultDetails[ 'mailbox' ] = $inbox[ 'inbox_mailbox' ];
            $resultDetails[ 'noValidate' ] = $inbox[ 'inbox_no_validate' ] == 1;
            $resultDetails[ 'leaveMessages' ] = $inbox[ 'inbox_leave_messages' ] == 1;
            $resultDetails[ 'allowExternal' ] = $inbox[ 'inbox_allow_external' ] == 1;
            $resultDetails[ 'robot' ] = $inbox[ 'inbox_robot' ] != null ? $inbox[ 'inbox_robot' ] : null;
            $resultDetails[ 'mapFolder' ] = $inbox[ 'inbox_map_folder' ] == 1;
            $resultDetails[ 'defaultFolder' ] = $inbox[ 'inbox_default_folder' ] != null ? $inbox[ 'inbox_default_folder' ] : null;
            if ( $engine != null ) {
                $resultDetails[ 'respond' ] = $inbox[ 'inbox_respond' ] == 1;
                $resultDetails[ 'subscribe' ] = $inbox[ 'inbox_subscribe' ] == 1;
            }

            $result[ 'details' ] = $resultDetails;

            $result[ 'emailEngine' ] = $engine;
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Inboxes_Load' );
