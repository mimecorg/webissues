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

class Admin_Info_Database extends System_Web_Component
{
    private $config = null;

    protected function __construct( $config )
    {
        parent::__construct();

        $this->config = $config;
    }

    protected function execute()
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        $this->dbServer = $connection->getParameter( 'server' );
        $this->dbVersion = $connection->getParameter( 'version' );

        if ( $this->config == null ) {
            $site = System_Core_Application::getInstance()->getSite();

            $this->dbHost = $site->getConfig( 'db_host' );
            $this->dbDatabase = $site->getConfig( 'db_database' );
            $this->dbPrefix = $site->getConfig( 'db_prefix' );
        } else {
            $this->dbHost = $this->config[ 0 ];
            $this->dbDatabase = $this->config[ 1 ];
            $this->dbPrefix = $this->config[ 2 ];
        }
    }
}
