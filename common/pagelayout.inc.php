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

if ( !defined( 'WI_VERSION' ) ) die( -1 );

class Common_PageLayout extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $application = System_Core_Application::getInstance();

        $this->siteName = $this->tr( 'WebIssues' );
        try {
            if ( $application->getConnection()->isOpened() ) {
                $serverManager = new System_Api_ServerManager();
                $server = $serverManager->getServer();
                $this->siteName = $server[ 'server_name' ];
            }
        } catch ( Exception $ex ) {
            $application->handleException( $ex );
        }

        $this->pageTitle = $this->view->getSlot( 'page_title', $this->tr( 'Untitled page' ) );

        $principal = System_Api_Principal::getCurrent();
        if ( $principal->isAuthenticated() )
            $this->homeUrl = '/client/index.php';
        else
            $this->homeUrl = '/index.php';

        $scriptFiles = $this->view->getSlot( 'script_files' );

        $this->scriptFiles[] = '/common/js/jquery.js';
        $this->scriptFiles[] = '/common/js/jquery.cookie.js';
        $this->scriptFiles[] = '/common/js/webissues.min.js';

        if ( !empty( $scriptFiles ) ) {
            foreach ( $scriptFiles as $file )
                $this->scriptFiles[] = $file;
        }

        $cssFiles = $this->view->getSlot( 'css_files' );

        if ( !empty( $cssFiles ) ) {
            foreach ( $cssFiles as $file )
                $this->cssFiles[] = $file;
        }

        $this->cssFiles[] = '/common/theme/style.css';

        $this->cssConditional[ 'lt IE 8' ] = '/common/theme/ie7.css';

        $inlineCode = $this->view->getSlot( 'inline_code' );
        if ( !empty( $inlineCode ) )
            $this->inlineCode = new System_Web_RawValue( "    $( function() {" . join( '', $inlineCode ) . "\n    } );\n" );

        $this->icon = '/common/images/webissues.ico';

        $this->manualUrl = $application->getManualUrl();

        if ( $application->isDebugInfoEnabled() && $application->getFatalError() == null ) {
            $this->errors = array();
            foreach ( $application->getErrors() as $exception )
                $this->errors[] = $exception->__toString();
        }
    }
}
