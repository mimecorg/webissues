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

        $userManager = new System_Api_UserManager();
        $rights = $userManager->getRights();

        $result[ 'projects' ] = array();

        $isProjectAdministrator = false;

        foreach ( $projects as $project ) {
            $resultProject = array();

            $resultProject[ 'id' ] = (int)$project[ 'project_id' ];
            $resultProject[ 'name' ] = $project[ 'project_name' ];
            $resultProject[ 'access' ] = (int)$project[ 'project_access' ];
            $resultProject[ 'folders' ] = array();
            $resultProject[ 'members' ] = array();

            if ( $project[ 'project_access' ] == System_Const::AdministratorAccess )
                $isProjectAdministrator = true;

            foreach ( $folders as $folder ) {
                if ( $folder[ 'project_id' ] == $project[ 'project_id' ] ) {
                    $resultFolder = array();
                    $resultFolder[ 'id' ] = (int)$folder[ 'folder_id' ];
                    $resultFolder[ 'name' ] = $folder[ 'folder_name' ];
                    $resultFolder[ 'typeId' ] = (int)$folder[ 'type_id' ];
                    $resultProject[ 'folders' ][] = $resultFolder;
                }
            }

            foreach ( $rights as $right ) {
                if ( $right[ 'project_id' ] == $project[ 'project_id' ] )
                    $resultProject[ 'members' ][] = (int)$right[ 'user_id' ];
            }

            $result[ 'projects' ][] = $resultProject;
        }

        $typeManager = new System_Api_TypeManager();

        if ( $principal->isAdministrator() || $isProjectAdministrator )
            $types = $typeManager->getIssueTypes();
        else
            $types = $typeManager->getAvailableIssueTypes();

        $attributes = $typeManager->getAttributeTypes();

        $viewManager = new System_Api_ViewManager();
        $views = $viewManager->getViews();

        $formatter = new System_Api_Formatter();

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

                    $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
                    $resultAttribute[ 'type' ] = $info->getType();
                    foreach ( $info->getAllMetadata() as $key => $value ) {
                        if ( $key == 'default' ) {
                            $formatted = $formatter->convertInitialValueInfo( $info, $value, System_Api_Formatter::MultiLine );
                            $resultAttribute[ $key ] = $formatted;
                        } else {
                            $resultAttribute[ $key ] = $value;
                        }
                    }

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

        if ( $principal->isAdministrator() || $isProjectAdministrator )
            $users = $userManager->getUsers();
        else
            $users = $userManager->getVisibleUsers();

        $result[ 'users' ] = array();

        foreach ( $users as $user ) {
            $resultUser = array();
            $resultUser[ 'id' ] = (int)$user[ 'user_id' ];
            $resultUser[ 'name' ] = $user[ 'user_name' ];
            $result[ 'users' ][] = $resultUser;
        }

        $serverManager = new System_Api_ServerManager();
        $settings[ 'commentMaxLength' ] = (int)$serverManager->getSetting( 'comment_max_length' );
        $settings[ 'fileMaxSize' ] = (int)$serverManager->getSetting( 'file_max_size' );
        $settings[ 'hideEmptyValues' ] = $serverManager->getSetting( 'hide_empty_values' ) == '1';

        $preferencesManager = new System_Api_PreferencesManager();
        $settings[ 'historyOrder' ] = $preferencesManager->getPreferenceOrSetting( 'history_order' );
        $settings[ 'defaultFormat' ] = (int)$preferencesManager->getPreferenceOrSetting( 'default_format' );

        $locale = new System_Api_Locale();

        $info = System_Api_DefinitionInfo::fromString( $locale->getSettingFormat( 'number_format' ) );
        $settings[ 'groupSeparator' ] = $info->getMetadata( 'group-separator' );
        $settings[ 'decimalSeparator' ] = $info->getMetadata( 'decimal-separator' );

        $info = System_Api_DefinitionInfo::fromString( $locale->getSettingFormat( 'date_format' ) );
        $settings[ 'dateOrder' ] = $info->getMetadata( 'date-order' );
        $settings[ 'dateSeparator' ] = $info->getMetadata( 'date-separator' );
        $settings[ 'padMonth' ] = $info->getMetadata( 'pad-month' ) == 1;
        $settings[ 'padDay' ] = $info->getMetadata( 'pad-day' ) == 1;

        $info = System_Api_DefinitionInfo::fromString( $locale->getSettingFormat( 'time_format' ) );
        $settings[ 'timeMode' ] = $info->getMetadata( 'time-mode' );
        $settings[ 'timeSeparator' ] = $info->getMetadata( 'time-separator' );
        $settings[ 'padHours' ] = $info->getMetadata( 'pad-hour' ) == 1;

        $settings[ 'firstDayOfWeek' ] = (int)$locale->getSetting( 'first_day_of_week' );

        $result[ 'settings' ] = $settings;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Global' );
