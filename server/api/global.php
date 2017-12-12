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

require_once( '../../system/bootstrap.inc.php' );

class Server_Api_Global
{
    public function run( $arguments )
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $result[ 'serverName' ] = $server[ 'server_name' ];
        $result[ 'serverVersion' ] = $server[ 'server_version' ];

        $principal = System_Api_Principal::getCurrent();

        $result[ 'userId' ] = $principal->getUserId();
        $result[ 'userName' ] = $principal->getUserName();
        $result[ 'userAccess' ] = $principal->getUserAccess();

        $projectManager = new System_Api_ProjectManager();
        $projects = $projectManager->getProjects();
        $folders = $projectManager->getFolders();

        $result[ 'projects' ] = array();

        foreach ( $projects as $project ) {
            $resultProject = array();

            $resultProject[ 'id' ] = (int)$project[ 'project_id' ];
            $resultProject[ 'name' ] = $project[ 'project_name' ];
            $resultProject[ 'access' ] = (int)$project[ 'project_access' ];
            $resultProject[ 'folders' ] = array();

            foreach ( $folders as $folder ) {
                if ( $folder[ 'project_id' ] == $project[ 'project_id' ] ) {
                    $resultFolder = array();
                    $resultFolder[ 'id' ] = (int)$folder[ 'folder_id' ];
                    $resultFolder[ 'name' ] = $folder[ 'folder_name' ];
                    $resultFolder[ 'typeId' ] = (int)$folder[ 'type_id' ];
                    $resultProject[ 'folders' ][] = $resultFolder;
                }
            }

            $result[ 'projects' ][] = $resultProject;
        }

        $typeManager = new System_Api_TypeManager();
        $types = $typeManager->getAvailableIssueTypes();
        $attributes = $typeManager->getAttributeTypes();

        $viewManager = new System_Api_ViewManager();
        $views = $viewManager->getViews();

        $result[ 'types' ] = array();

        foreach ( $types as $type ) {
            $resultType = array();

            $resultType[ 'id' ] = (int)$type[ 'type_id' ];
            $resultType[ 'name' ] = $type[ 'type_name' ];

            $resultType[ 'attributes' ] = array();

            $typeAttributes = array();
            foreach ( $attributes as $attribute ) {
                if ( $attribute[ 'type_id' ] == $type[ 'type_id' ] )
                    $typeAttributes[] = $attribute;
            }

            if ( count( $typeAttributes ) > 0 ) {
                $typeAttributes = $viewManager->sortByAttributeOrder( $type, $typeAttributes );

                foreach ( $typeAttributes as $attribute ) {
                    $resultAttribute = array();
                    $resultAttribute[ 'id' ] = (int)$attribute[ 'attr_id' ];
                    $resultAttribute[ 'name' ] = $attribute[ 'attr_name' ];
                    $resultType[ 'attributes' ][] = $resultAttribute;
                }
            }

            $resultType[ 'views' ] = array();

            foreach ( $views as $view ) {
                if ( $view[ 'type_id' ] == $type[ 'type_id' ] ) {
                    $resultView = array();
                    $resultView[ 'id' ] = (int)$view[ 'view_id' ];
                    $resultView[ 'name' ] = $view[ 'view_name' ];
                    $resultView[ 'public' ] = $view[ 'is_public' ] != 0;
                    $resultType[ 'views' ][] = $resultView;
                }
            }

            $result[ 'types' ][] = $resultType;
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Global' );
