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

class Common_Errors_Setup extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Window' );
        $this->view->setSlot( 'window_size', 'small' );

        $application = System_Core_Application::getInstance();
        $error = $application->getFatalError();

        switch ( $error->getCode() ) {
            case System_Core_SetupException::SiteConfigNotFound:
                $this->view->setSlot( 'page_title', $this->tr( 'Welcome to WebIssues' ) );
                $this->infoMessage = $this->tr( 'Your WebIssues Server is almost ready. We just need to set up the database.' );
                $this->alertClass = 'info';
                $this->linkUrl = '/admin/setup/install.php';
                $this->linkName = $this->tr( 'Configure Database' );
                break;

            case System_Core_SetupException::DatabaseNotCompatible:
                $this->view->setSlot( 'page_title', $this->tr( 'Incompatible Database' ) );
                $this->infoMessage = $this->tr( 'The database is not compatible with this version of WebIssues.' );
                $this->alertClass = 'danger';
                break;

            case System_Core_SetupException::DatabaseNotUpdated:
                $this->view->setSlot( 'page_title', $this->tr( 'Update Required' ) );
                $this->infoMessage = $this->tr( 'The database must be updated to the current version of WebIssues.' );
                $this->alertClass = 'info';
                $this->linkUrl = '/admin/setup/update.php';
                $this->linkName = $this->tr( 'Update Database' );
                break;
        }
   }
}
