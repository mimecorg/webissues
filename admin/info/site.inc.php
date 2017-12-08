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

class Admin_Info_Site extends System_Web_Component
{
    protected function __construct( $form )
    {
        parent::__construct();

        $this->form = $form;
    }

    protected function execute()
    {
        $site = System_Core_Application::getInstance()->getSite();

        $this->siteName = $site->getSiteName();

        $siteDirectory = $site->getPath( 'site_dir' );
        $this->siteDirectory = System_Core_FileSystem::toNativeSeparators( $siteDirectory );

        $storageDirectory = $siteDirectory . '/storage';
        $this->storageDirectory = System_Core_FileSystem::toNativeSeparators( $storageDirectory );

        $this->debugLevel = $site->getConfig( 'debug_level' );
        $this->debugInfo = $site->getConfig( 'debug_info' );

        if ( $this->debugLevel > 0 ) {
            $debugFile = $site->getPath( 'debug_file' );
            $this->debugFile = System_Core_FileSystem::toNativeSeparators( $debugFile );
        }

        if ( !System_Core_FileSystem::isDirectoryWritable( $siteDirectory ) )
            $this->form->setError( 'site', $this->tr( "Cannot access directory '%1'.", null, $this->siteDirectory ) );
        else if ( !System_Core_FileSystem::isDirectoryWritable( $storageDirectory ) )
            $this->form->setError( 'site', $this->tr( "Cannot access directory '%1'.", null, $this->storageDirectory ) );
        else if ( $this->debugLevel > 0 && !System_Core_FileSystem::isFileWritable( $debugFile ) )
            $this->form->setError( 'site', $this->tr( "Cannot access file '%1'.", null, $this->debugFile ) );

        $this->phpVersion = PHP_VERSION . ' (' . PHP_SAPI . ')';
        $this->webServer = $this->request->getServerSoftware();
        $this->osVersion = php_uname( 's' ) . ' ' . php_uname( 'r' );
        $this->hostName = php_uname( 'n' );
    }
}
