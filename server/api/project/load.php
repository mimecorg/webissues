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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Project_Load
{
    public function run( $arguments )
    {
        $projectId = isset( $arguments[ 'projectId' ] ) ? (int)$arguments[ 'projectId' ] : null;
        $description = isset( $arguments[ 'description' ] ) ? (bool)$arguments[ 'description' ] : false;
        $folders = isset( $arguments[ 'folders' ] ) ? (bool)$arguments[ 'folders' ] : false;
        $members = isset( $arguments[ 'members' ] ) ? (bool)$arguments[ 'members' ] : false;
        $html = isset( $arguments[ 'html' ] ) ? (bool)$arguments[ 'html' ] : false;
        $access = isset( $arguments[ 'access' ] ) ? $arguments[ 'access' ] : null;

        if ( $projectId == null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $flags = 0;
        if ( $access == 'admin' )
            $flags = System_Api_ProjectManager::RequireAdministrator;
        else if ( $access != null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId, $flags );

        $formatter = new System_Api_Formatter();

        $resultDetails[ 'id' ] = $project[ 'project_id' ];
        $resultDetails[ 'name' ] = $project[ 'project_name' ];
        $resultDetails[ 'access' ] = (int)$project[ 'project_access' ];
        $resultDetails[ 'public' ] = $project[ 'is_public' ] != 0;

        $result[ 'details' ] = $resultDetails;

        if ( $html )
            System_Web_Base::setLinkMode( System_Web_Base::RouteLinks );

        if ( $description ) {
            if ( $project[ 'descr_id' ] != null ) {
                $descr = $projectManager->getProjectDescription( $project );

                $resultDescription[ 'modifiedBy' ] = $descr[ 'modified_by' ];
                $resultDescription[ 'modifiedDate' ] = $formatter->formatDateTime( $descr[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );

                $resultDescription[ 'text' ] = $this->convertText( $descr[ 'descr_text' ], $html, $descr[ 'descr_format' ] );
                $resultDescription[ 'format' ] = $descr[ 'descr_format' ];

                $result[ 'description' ] = $resultDescription;
            } else {
                $result[ 'description' ] = null;
            }
        }

        if ( $folders ) {
            $folderRows = $projectManager->getFoldersForProject( $project );

            $result[ 'folders' ] = array();

            foreach ( $folderRows as $folder ) {
                $resultFolder = array();
                $resultFolder[ 'id' ] = (int)$folder[ 'folder_id' ];
                $resultFolder[ 'name' ] = $folder[ 'folder_name' ];
                $resultFolder[ 'typeId' ] = (int)$folder[ 'type_id' ];
                $result[ 'folders' ][] = $resultFolder;
            }
        }

        if ( $members ) {
            $userManager = new System_Api_UserManager();
            $memberRows = $userManager->getMembers( $project );

            $result[ 'members' ] = array();

            foreach ( $memberRows as $member ) {
                $resultMember = array();
                $resultMember[ 'id' ] = $member[ 'user_id' ];
                $resultMember[ 'access' ] = $member[ 'project_access' ];
                $result[ 'members' ][] = $resultMember;
            }
        }

        return $result;
    }

    private function convertText( $text, $html, $format = System_Const::PlainText )
    {
        if ( $html ) {
            if ( $format == System_Const::TextWithMarkup )
                return System_Web_MarkupProcessor::convertToHtml( $text, $prettyPrint );
            else
                return System_Web_LinkLocator::convertToHtml( $text );
        } else {
            return $text;
        }
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Project_Load' );
