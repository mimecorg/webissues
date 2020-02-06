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

class Server_Api_Users_Add
{
    public $access = 'admin';

    public $params = array(
        'name' => array( 'type' => 'string', 'required' => true ),
        'login' => array( 'type' => 'string', 'required' => true ),
        'sendInvitationEmail' => array( 'type' => 'bool', 'default' => false ),
        'password' => array( 'type' => 'string', 'default' => '' ),
        'mustChangePassword' => array( 'type' => 'bool', 'default' => false ),
        'email' => array( 'type' => 'string', 'default' => '' ),
        'language' => array( 'type' => 'string', 'default' => '' )
    );

    public function run( $name, $login, $sendInvitationEmail, $password, $mustChangePassword, $email, $language )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $name, System_Const::NameMaxLength );
        $validator->checkString( $login, System_Const::LoginMaxLength );
        $validator->checkString( $password, System_Const::PasswordMaxLength, $sendInvitationEmail ? System_Api_Validator::AllowEmpty : 0 );
        $validator->checkString( $email, System_Const::ValueMaxLength, $sendInvitationEmail ? 0 : System_Api_Validator::AllowEmpty );
        if ( $email != '' )
            $validator->checkEmailAddress( $email );
        $validator->checkString( $language, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        if ( $language != '' )
            $validator->checkLanguage( $language );

        if ( $sendInvitationEmail ) {
            $keyGenerator = new System_Api_KeyGenerator();
            $invitationKey = $keyGenerator->generateKey( System_Api_KeyGenerator::PasswordReset );
        } else {
            $invitationKey = null;
        }

        $userManager = new System_Api_UserManager();
        $userId = $userManager->addUser( $login, $name, $password, $mustChangePassword ? 1 : 0, $invitationKey, $email, $language );

        if ( $sendInvitationEmail ) {
            $data = array( 'user_login' => $login, 'user_name' => $name, 'user_email' => $email, 'invitation_key' => $invitationKey );

            $helper = new System_Mail_Helper();
            $helper->send( $email, $name, $language, 'Common_Mail_AccountCreated', $data );
        }

        $result[ 'userId' ] = $userId;
        $result[ 'changed' ] = true;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Add' );
