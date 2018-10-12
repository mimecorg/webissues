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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Users_Edit
{
    public $access = 'admin';

    public $params = array(
        'userId' => array( 'type' => 'int', 'required' => true ),
        'name' => array( 'type' => 'string', 'required' => true ),
        'login' => array( 'type' => 'string', 'required' => true ),
        'email' => array( 'type' => 'string', 'default' => '' ),
        'language' => array( 'type' => 'string', 'default' => '' )
    );

    public function run( $userId, $name, $login, $email, $language )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $name, System_Const::NameMaxLength );
        $validator->checkString( $login, System_Const::LoginMaxLength );
        $validator->checkString( $email, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        $validator->checkPreference( 'email', $email );
        $validator->checkPreference( 'language', $language );

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        $renamed = $userManager->renameUser( $user, $name );
        $loginChanged = $userManager->changeLogin( $user, $login );

        $preferencesManager = new System_Api_PreferencesManager( $user );
        $emailChanged = $preferencesManager->setPreference( 'email', $email );
        $languageChanged = $preferencesManager->setPreference( 'language', $language );

        $result[ 'userId' ] = $userId;
        $result[ 'changed' ] = $renamed || $loginChanged || $emailChanged || $languageChanged;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Edit' );
