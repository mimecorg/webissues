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

class Server_Api_Users_Password_Reset
{
    public $access = 'admin';

    public $params = array(
        'userId' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $userId )
    {
        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'email_engine' ) == null )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        if ( $user[ 'user_email' ] == null )
            throw new System_Api_Error( System_Api_Error::UnknownUser );

        $keyGenerator = new System_Api_KeyGenerator();
        $key = $keyGenerator->generateKey( System_Api_KeyGenerator::PasswordReset );

        $userManager->setPasswordResetKey( $user, $key );

        $data = array( 'user_login' => $user[ 'user_login' ], 'user_name' => $user[ 'user_name' ], 'user_email' => $user[ 'user_email' ], 'reset_key' => $key );

        $helper = new System_Mail_Helper();
        $helper->send( $user[ 'user_email' ], $user[ 'user_name' ], $user[ 'user_language' ], 'Common_Mail_ResetPassword', $data );
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Password_Reset' );
