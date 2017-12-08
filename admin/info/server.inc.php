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

class Admin_Info_Server extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $serverManager = new System_Api_ServerManager();
        $this->server = $serverManager->getServer();

        if ( $this->request->isRelativePathUnder( '/admin/info' ) ) {
            $this->toolBar = new System_Web_ToolBar();
            $this->toolBar->addFixedCommand( '/admin/info/renameserver.php', '/common/images/edit-rename-16.png', $this->tr( 'Rename Server' ) );
            $this->toolBar->addFixedCommand( '/admin/info/generateuuid.php', '/common/images/edit-modify-16.png', $this->tr( 'Generate New Unique ID' ) );
        }
    }
}
