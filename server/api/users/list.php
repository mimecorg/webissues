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

class Server_Api_Users_List
{
    public $access = 'admin';

    public $params = array();

    public function run()
    {
        $userManager = new System_Api_UserManager();
        $users = $userManager->getUsersWithDetails();

        $result[ 'users' ] = array();

        foreach ( $users as $user ) {
            $resultUser = array();

            $resultUser[ 'id' ] = $user[ 'user_id' ];
            $resultUser[ 'name' ] = $user[ 'user_name' ];
            $resultUser[ 'login' ] = $user[ 'user_login' ];
            $resultUser[ 'access' ] = $user[ 'user_access' ];
            $resultUser[ 'email' ] = $user[ 'user_email' ];
            $resultUser[ 'projectAdmin' ] = $user[ 'project_admin' ];

            $result[ 'users' ][] = $resultUser;
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_List' );
