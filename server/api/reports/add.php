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

class Server_Api_Reports_Add
{
    public $access = '*';

    public $params = array(
        'typeId' => array( 'type' => 'int', 'required' => true ),
        'viewId' => array( 'type' => 'int' ),
        'projectId' => array( 'type' => 'int' ),
        'folderId' => array( 'type' => 'int' ),
        'isPublic' => array( 'type' => 'bool', 'required' => true ),
        'alertType' => array( 'type' => 'int', 'required' => true ),
        'alertFrequency' => array( 'type' => 'int' ),
    );

    public function run( $typeId, $viewId, $projectId, $folderId, $isPublic, $alertType, $alertFrequency )
    {
        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'email_engine' ) == null )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        if ( $isPublic && !System_Api_Principal::getCurrent()->isAdministrator() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        if ( $projectId != null && $folderId != null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );

        if ( $viewId != null ) {
            $viewManager = new System_Api_ViewManager();
            $view = $viewManager->getView( $viewId );
        }

        if ( $projectId != null || $folderId != null ) {
            $projectManager = new System_Api_ProjectManager();
            if ( $projectId != null ) {
                $project = $projectManager->getProject( $projectId );
            } else {
                $folder = $projectManager->getFolder( $folderId );
                if ( $folder[ 'type_id' ] != $typeId )
                    throw new System_Api_Error( System_Api_Error::UnknownFolder );
            }
        }

        if ( $alertType < System_Const::ChangeReport || $alertType > System_Const::IssueReport )
            throw new System_Api_Error( System_Api_Error::InvalidAlertType );

        if ( $alertFrequency < System_Const::Daily || $alertFrequency > System_Const::Weekly )
            throw new System_Api_Error( System_Api_Error::InvalidAlertFrequency );

        $alertManager = new System_Api_AlertManager();

        $result[ 'reportId' ] = $alertManager->addAlert( $type, $view, $project, $folder, $alertType, $alertFrequency, $isPublic ? System_Api_AlertManager::IsPublic : 0 );
        $result[ 'changed' ] = true;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Reports_Add' );
