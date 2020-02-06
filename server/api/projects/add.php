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

class Server_Api_Projects_Add
{
    public $access = 'admin';

    public $params = array(
        'name' => array( 'type' => 'string', 'required' => true ),
        'description' => 'string',
        'descriptionFormat' => array( 'type' => 'int', 'default' => System_Const::PlainText )
    );

    public function run( $name, $description, $descriptionFormat )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $name, System_Const::NameMaxLength );

        if ( $description != null ) {
            $serverManager = new System_Api_ServerManager();
            $validator->checkString( $description, $serverManager->getSetting( 'comment_max_length' ), System_Api_Validator::AllowEmpty | System_Api_Validator::MultiLine );
            $validator->checkTextFormat( $descriptionFormat );
        }

        $projectManager = new System_Api_ProjectManager();

        $projectId = $projectManager->addProject( $name );

        if ( $description != null ) {
            $project = $projectManager->getProject( $projectId );
            $projectManager->addProjectDescription( $project, $description, $descriptionFormat );
        }

        $result[ 'projectId' ] = $projectId;
        $result[ 'changed' ] = true;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Projects_Add' );
