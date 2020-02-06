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

class Server_Api_Users_Projects_Edit
{
    public $access = 'admin';

    public $params = array(
        'userId' => array( 'type' => 'int', 'required' => true ),
        'projects' => array( 'type' => 'array', 'required' => true ),
        'access' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $userId, $projects, $access )
    {
        $validator = new System_Api_Validator();
        $validator->checkAccessLevel( $access );

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        $projectManager = new System_Api_ProjectManager();

        $projectRows = array();
        foreach ( $projects as $projectId ) {
            if ( !is_int( $projectId ) )
                throw new Server_Error( Server_Error::InvalidArguments );
            $projectRows[] = $projectManager->getProject( $projectId );
        }

        $changed = false;

        foreach ( $projectRows as $project ) {
            if ( $userManager->grantMember( $user, $project, $access ) )
                $changed = true;
        }

        $result[ 'changed' ] = $changed;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Projects_Edit' );
