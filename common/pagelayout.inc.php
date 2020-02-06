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

        $this->siteName = 'WebIssues';
        try {
            if ( $application->getConnection()->isOpened() ) {
                $serverManager = new System_Api_ServerManager();
                $server = $serverManager->getServer();
                $this->siteName = $server[ 'server_name' ];
            }
        } catch ( Exception $ex ) {
            $application->handleException( $ex );
        }

        $this->icon = '/common/images/webissues.ico';
        $this->touchIcon = '/common/images/apple-touch-icon.png';

        $this->assets = new System_Web_Assets();
        $this->assets->add( 'client' );

        $this->manualUrl = 'http://doc.mimec.org/webissues/1.1/en/index.html';
    }
}
