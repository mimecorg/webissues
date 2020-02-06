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

class Server_Api_Users_Load
{
    public $access = 'admin';

    public $params = array(
        'userId' => array( 'type' => 'int', 'required' => true ),
        'projects' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $userId, $projects )
    {
        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        $resultDetails[ 'id' ] = $user[ 'user_id' ];
        $resultDetails[ 'name' ] = $user[ 'user_name' ];
        $resultDetails[ 'login' ] = $user[ 'user_login' ];
        $resultDetails[ 'access' ] = $user[ 'user_access' ];
        $resultDetails[ 'email' ] = $user[ 'user_email' ];
        $resultDetails[ 'language' ] = $user[ 'user_language' ];

        $result[ 'details' ] = $resultDetails;

        if ( $projects ) {
            $projectRows = $userManager->getUserProjects( $user );

            $result[ 'projects' ] = array();

            foreach ( $projectRows as $project ) {
                $resultProject = array();
                $resultProject[ 'id' ] = $project[ 'project_id' ];
                $resultProject[ 'access' ] = $project[ 'project_access' ];
                $result[ 'projects' ][] = $resultProject;
            }
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Load' );
