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

require_once( '../../system/bootstrap.inc.php' );

class Server_Api_Login
{
    public $access = 'public';

    public $params = array(
        'login' => array( 'type' => 'string', 'required' => true ),
        'password' => array( 'type' => 'string', 'required' => true ),
        'newPassword' => 'string'
    );

    public function run( $login, $password, $newPassword )
    {
        $parser = new System_Api_Parser();
        $login = $parser->normalizeString( $login, System_Const::NameMaxLength );
        $password = $parser->normalizeString( $password, System_Const::PasswordMaxLength );
        if ( $newPassword != null )
            $newPassword = $parser->normalizeString( $newPassword, System_Const::PasswordMaxLength );

        $sessionManager = new System_Api_SessionManager();
        $user = $sessionManager->login( $login, $password, $newPassword );

        $result[ 'userId' ] = $user[ 'user_id' ];
        $result[ 'userName' ] = $user[ 'user_name' ];
        $result[ 'userAccess' ] = $user[ 'user_access' ];

        $serverManager = new System_Api_ServerManager();
        $csrfToken = $serverManager->generateUuid();

        $session = System_Core_Application::getInstance()->getSession();
        $session->setValue( 'CSRF_TOKEN', $csrfToken );

        $result[ 'csrfToken' ] = $csrfToken;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Login' );
