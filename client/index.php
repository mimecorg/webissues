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

require_once( '../system/bootstrap.inc.php' );

class Client_Index extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $this->getView()->setDecoratorClass( null );

        $principal = System_Api_Principal::getCurrent();
        $session = System_Core_Application::getInstance()->getSession();
        $serverManager = new System_Api_ServerManager();

        if ( $principal->isAuthenticated() ) {
            $csrfToken = $session->getValue( 'CSRF_TOKEN' );
            if ( $csrfToken == null ) {
                $csrfToken = $serverManager->generateUuid();
                $session->setValue( 'CSRF_TOKEN', $csrfToken );
            }
        } else {
            if ( $session->isDestroyed() || $serverManager->getSetting( 'anonymous_access' ) != 1 )
                $this->response->redirect( '/index.php' );
            $csrfToken = null;
        }

        $server = $serverManager->getServer();
        $this->siteName = $server[ 'server_name' ];

        $this->icon = '/common/images/webissues.ico';

        $site = System_Core_Application::getInstance()->getSite();
        $devMode = $site->getConfig( 'dev_mode' );
        $devUrl = $site->getConfig( 'dev_url' );

        if ( !$devMode ) {
            $assetsPath = WI_ROOT_DIR . '/assets/assets.json';

            if ( !file_exists( $assetsPath ) )
                throw new System_Core_Exception( 'Assets were not built' );

            $assets = json_decode( file_get_contents( $assetsPath ), true );

            $this->styleUrl = '/assets/' . $assets[ 'common' ][ 'css' ];
            $this->commonScriptUrl = '/assets/' . $assets[ 'common' ][ 'js' ];
            $this->mainScriptUrl = '/assets/' . $assets[ 'main' ][ 'js' ];
        } else {
            $this->styleUrl = null;
            $this->commonScriptUrl = $devUrl . 'js/common.js';
            $this->mainScriptUrl = $devUrl . 'js/main.js';
        }

        $options[ 'baseURL' ] = WI_BASE_URL;
        $options[ 'csrfToken' ] = $csrfToken;

        $options[ 'locale' ] = $this->translator->getLanguage( System_Core_Translator::UserLanguage );

        $options[ 'serverName' ] = $server[ 'server_name' ];
        $options[ 'serverVersion' ] = $server[ 'server_version' ];

        $options[ 'userId' ] = $principal->getUserId();
        $options[ 'userName' ] = $principal->getUserName();
        $options[ 'userAccess' ] = $principal->getUserAccess();

        $this->options = new System_Web_RawValue( json_encode( $options ) );
    }
}

System_Bootstrap::run( 'Common_Application', 'Client_Index' );
