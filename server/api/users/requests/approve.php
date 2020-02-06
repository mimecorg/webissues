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

class Server_Api_Users_Requests_Approve
{
    public $access = 'admin';

    public $params = array(
        'requestId' => array( 'type' => 'int', 'required' => true ),
        'projects' => array( 'type' => 'array', 'required' => true )
    );

    public function run( $requestId, $projects )
    {
        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'self_register' ) != 1 || $serverManager->getSetting( 'email_engine' ) == null || $serverManager->getSetting( 'register_auto_approve' ) == 1 )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $registrationManager = new System_Api_RegistrationManager();
        $request = $registrationManager->getRequest( $requestId );

        $projectManager = new System_Api_ProjectManager();

        $projectRows = array();
        foreach ( $projects as $projectId ) {
            if ( !is_int( $projectId ) )
                throw new Server_Error( Server_Error::InvalidArguments );
            $projectRows[] = $projectManager->getProject( $projectId );
        }

        $userId = $registrationManager->approveRequest( $request );

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        foreach ( $projectRows as $project )
            $userManager->grantMember( $user, $project, System_Const::NormalAccess );

        $helper = new System_Mail_Helper();
        $helper->send( $request[ 'user_email' ], $request[ 'user_name' ], null, 'Common_Mail_RegistrationApproved', $request );

        $result[ 'userId' ] = $userId;
        $result[ 'changed' ] = true;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Users_Requests_Approve' );
