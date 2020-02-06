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

require_once( '../../system/bootstrap.inc.php' );

class Server_Api_Global
{
    public $access = 'anonymous';

    public $params = array();

    public function run()
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $result[ 'serverName' ] = $server[ 'server_name' ];
        $result[ 'serverVersion' ] = $server[ 'server_version' ];
        $result[ 'serverUUID' ] = $server[ 'server_uuid' ];

        $principal = System_Api_Principal::getCurrent();

        $result[ 'userId' ] = $principal->getUserId();
        $result[ 'userName' ] = $principal->getUserName();
        $result[ 'userAccess' ] = $principal->getUserAccess();
        $result[ 'userEmail' ] = $principal->getUserEmail();

        $projectManager = new System_Api_ProjectManager();
        $projects = $projectManager->getProjects();
        $folders = $projectManager->getFolders();

        $userManager = new System_Api_UserManager();
        $rights = $userManager->getRights();

        $result[ 'projects' ] = array();

        foreach ( $projects as $project ) {
            $resultProject = array();

            $resultProject[ 'id' ] = $project[ 'project_id' ];
            $resultProject[ 'name' ] = $project[ 'project_name' ];
            $resultProject[ 'access' ] = $project[ 'project_access' ];
            $resultProject[ 'folders' ] = array();
            $resultProject[ 'members' ] = array();

            foreach ( $folders as $folder ) {
                if ( $folder[ 'project_id' ] == $project[ 'project_id' ] ) {
                    $resultFolder = array();
                    $resultFolder[ 'id' ] = $folder[ 'folder_id' ];
                    $resultFolder[ 'name' ] = $folder[ 'folder_name' ];
                    $resultFolder[ 'typeId' ] = $folder[ 'type_id' ];
                    $resultProject[ 'folders' ][] = $resultFolder;
                }
            }

            foreach ( $rights as $right ) {
                if ( $right[ 'project_id' ] == $project[ 'project_id' ] )
                    $resultProject[ 'members' ][] = $right[ 'user_id' ];
            }

            $result[ 'projects' ][] = $resultProject;
        }

        $typeManager = new System_Api_TypeManager();

        $types = $typeManager->getIssueTypes();
        $attributes = $typeManager->getAttributeTypes();

        $viewManager = new System_Api_ViewManager();
        $views = $viewManager->getViews();

        $viewPreferences = $viewManager->getViewPreferences();

        $result[ 'types' ] = array();

        foreach ( $types as $type ) {
            $resultType = array();

            $resultType[ 'id' ] = $type[ 'type_id' ];
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
                    $resultAttribute[ 'id' ] = $attribute[ 'attr_id' ];
                    $resultAttribute[ 'name' ] = $attribute[ 'attr_name' ];

                    $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
                    $resultAttribute[ 'type' ] = $info->getType();
                    foreach ( $info->getAllMetadata() as $key => $value )
                        $resultAttribute[ $key ] = $value;

                    $resultType[ 'attributes' ][] = $resultAttribute;
                }
            }

            $resultType[ 'views' ] = array();

            foreach ( $views as $view ) {
                if ( $view[ 'type_id' ] == $type[ 'type_id' ] ) {
                    $resultView = array();
                    $resultView[ 'id' ] = $view[ 'view_id' ];
                    $resultView[ 'name' ] = $view[ 'view_name' ];
                    $resultView[ 'public' ] = $view[ 'is_public' ] != 0;
                    $resultType[ 'views' ][] = $resultView;
                }
            }

            foreach ( $viewPreferences as $preference ) {
                if ( $preference[ 'type_id' ] == $type[ 'type_id' ] && $preference[ 'pref_key' ] == 'initial_view' )
                    $resultType[ 'initialView' ] = (int)$preference[ 'pref_value' ];
            }

            $result[ 'types' ][] = $resultType;
        }

        $users = $userManager->getUsers();

        $result[ 'users' ] = array();

        foreach ( $users as $user ) {
            $resultUser = array();
            $resultUser[ 'id' ] = $user[ 'user_id' ];
            $resultUser[ 'name' ] = $user[ 'user_name' ];
            $result[ 'users' ][] = $resultUser;
        }

        $serverManager = new System_Api_ServerManager();
        $settings[ 'commentMaxLength' ] = (int)$serverManager->getSetting( 'comment_max_length' );
        $settings[ 'fileMaxSize' ] = (int)$serverManager->getSetting( 'file_max_size' );
        $settings[ 'hideEmptyValues' ] = $serverManager->getSetting( 'hide_empty_values' ) == '1';
        $settings[ 'selfRegister' ] = $serverManager->getSetting( 'self_register' ) == 1 && $serverManager->getSetting( 'email_engine' ) != null;
        $settings[ 'registerAutoApprove' ] = $serverManager->getSetting( 'register_auto_approve' ) == 1;
        $settings[ 'resetPassword' ] = $serverManager->getSetting( 'email_engine' ) != null;
        $settings[ 'reports' ] = $serverManager->getSetting( 'email_engine' ) != null;
        $settings[ 'subscriptions' ] = $serverManager->getSetting( 'email_engine' ) != null;

        $preferencesManager = new System_Api_PreferencesManager();
        $historyFilter = $preferencesManager->getPreference( 'history_filter' );
        if ( $historyFilter != null )
            $settings[ 'historyFilter' ] = (int)$historyFilter;
        else
            $settings[ 'historyFilter' ] = System_Api_HistoryProvider::AllHistory;

        $settings[ 'historyOrder' ] = $serverManager->getSetting( 'history_order' );
        $settings[ 'defaultFormat' ] = (int)$serverManager->getSetting( 'default_format' );

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

        $settings[ 'defaultLanguage' ] = $serverManager->getSetting( 'language' );

        $result[ 'settings' ] = $settings;

        $languages = $locale->getAvailableLanguages();

        $result[ 'languages' ] = array();

        foreach ( $languages as $key => $name ) {
            $resultLanguage = array();
            $resultLanguage[ 'key' ] = $key;
            $resultLanguage[ 'name' ] = $name;

            $result[ 'languages' ][] = $resultLanguage;
        }

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Global' );
