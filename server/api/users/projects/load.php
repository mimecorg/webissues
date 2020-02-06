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

class Server_Api_Users_Projects_Load
{
    public $access = 'admin';

    public $params = array(
        'userId' => array( 'type' => 'int', 'required' => true ),
        'projectId' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $userId, $projectId )
    {
        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId );

        $member = $userManager->getMember( $user, $project );

        $result[ 'userName' ] = $user[ 'user_name' ];
        $result[ 'projectName' ] = $project[ 'project_name' ];
        $result[ 'access' ] = $member[ 'project_access' ];

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Projects_Load' );
