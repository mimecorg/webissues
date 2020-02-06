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

class Server_Api_Issues_Files_Delete
{
    public $access = '*';

    public $params = array(
        'fileId' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $fileId )
    {
        $issueManager = new System_Api_IssueManager();
        $file = $issueManager->getFile( $fileId, System_Api_IssueManager::RequireAdministratorOrOwner );

        $stampId = $issueManager->deleteFile( $file );

        $result[ 'stampId' ] = $stampId;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_Files_Delete' );
