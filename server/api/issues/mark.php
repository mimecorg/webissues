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

class Server_Api_Issues_Mark
{
    public $access = '*';

    public $params = array(
        'typeId' => 'int',
        'viewId' => 'int',
        'projectId' => 'int',
        'folderId' => 'int',
        'searchColumn' => array( 'type' => 'int', 'default' => System_Api_Column::Name ),
        'searchValue' => 'string',
        'read' => array( 'type' => 'bool', 'required' => true )
    );

    public function run( $typeId, $viewId, $projectId, $folderId, $searchColumn, $searchValue, $read )
    {
        if ( $typeId == null && $viewId == null && $folderId == null )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $viewId != null && $typeId != null )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $folderId != null && ( $typeId != null || $projectId != null ) )
            throw new Server_Error( Server_Error::InvalidArguments );

        $typeManager = new System_Api_TypeManager();
        $viewManager = new System_Api_ViewManager();
        $projectManager = new System_Api_ProjectManager();

        if ( $viewId != null ) {
            $view = $viewManager->getView( $viewId );
            $type = $typeManager->getIssueTypeForView( $view );
        } else if ( $typeId != null ) {
            $type = $typeManager->getIssueType( $typeId );
            $view = null;
        } else {
            $type = null;
            $view = null;
        }

        if ( $folderId != null ) {
            $folder = $projectManager->getFolder( $folderId );
            if ( $type != null && $type[ 'type_id' ] != $folder[ 'type_id' ] )
                throw new System_Api_Error( System_Api_Error::UnknownFolder );
            $type = $typeManager->getIssueTypeForFolder( $folder );
            $project = null;
        } else if ( $projectId != null ) {
            $project = $projectManager->getProject( $projectId );
            $folder = null;
        } else {
            $project = null;
            $folder = null;
        }

        $queryGenerator = new System_Api_QueryGenerator();

        $queryGenerator->setIssueType( $type );

        if ( $folder != null )
            $queryGenerator->setFolder( $folder );
        else if ( $project != null )
            $queryGenerator->setProject( $project );

        if ( $view != null )
            $definition = $view[ 'view_def' ];
        else
            $definition = $viewManager->getViewSetting( $type, 'default_view' );

        if ( $definition != null )
            $queryGenerator->setViewDefinition( $definition );

        if ( $searchValue != null ) {
            $validator = new System_Api_Validator();

            $validator->checkString( $searchValue, System_Const::ValueMaxLength );

            $searchHelper = new Server_Api_Helpers_Search();

            $info = $searchHelper->getSearchValueInfo( $searchColumn );
            $definition = $info->toString();

            $validator->checkAttributeValue( $definition, $searchValue );

            $queryGenerator->setSearchValue( $searchColumn, $info->getType(), $searchValue );
        }

        $subQuery = $queryGenerator->generateIdsQuery();
        $arguments = $queryGenerator->getQueryArguments();

        if ( $read ) {
            if ( $folder != null ) {
                $readId = $folder[ 'stamp_id' ];
            } else {
                if ( $project != null )
                    $folders = $projectManager->getFoldersForProject( $project );
                else
                    $folders = $projectManager->getFoldersByIssueType( $type );
                $readId = 0;
                foreach ( $folders as $folder ) {
                    if ( $folder[ 'stamp_id' ] > $readId )
                        $readId = $folder[ 'stamp_id' ];
                }
            }
        } else {
            $readId = 0;
        }

        $stateManager = new System_Api_StateManager();

        $stateManager->setRead( $subQuery, $arguments, $readId );
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_Mark' );
