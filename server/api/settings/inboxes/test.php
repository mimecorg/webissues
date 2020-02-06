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

class Server_Api_Settings_Inboxes_Test
{
    public $access = 'admin';

    public $params = array(
        'engine' => array( 'type' => 'string', 'required' => true ),
        'email' => array( 'type' => 'string', 'required' => true ),
        'server' => array( 'type' => 'string', 'required' => true ),
        'port' => array( 'type' => 'int', 'required' => true ),
        'encryption' => 'string',
        'user' => 'string',
        'password' => 'string',
        'mailbox' => 'string',
        'noValidate' => 'bool'
    );

    public function run( $engine, $email, $server, $port, $encryption, $user, $password, $mailbox, $noValidate )
    {
        $helper = new Server_Api_Helpers_Inboxes();
        $helper->validateBasic( $engine, $email, $server, $port, $encryption, $user, $password, $mailbox );

        $inbox = array(
            'inbox_engine' => $engine,
            'inbox_email' => $email,
            'inbox_server' => $server,
            'inbox_port' => $port,
            'inbox_encryption' => $encryption,
            'inbox_user' => $user,
            'inbox_password' => $password,
            'inbox_mailbox' => $mailbox,
            'inbox_no_validate' => $noValidate ? 1 : 0
        );

        try {
            $inboxEngine = new System_Mail_InboxEngine();
            $inboxEngine->setSettings( $inbox );
            $inboxEngine->getMessagesCount();
            $status = true;
        } catch ( Exception $ex ) {
            $status = false;
        }

        $result[ 'status' ] = $status;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Inboxes_Test' );
