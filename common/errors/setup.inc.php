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
        $this->view->setDecoratorClass( 'Common_FixedBlock' );

        $application = System_Core_Application::getInstance();
        $error = $application->getFatalError();

        switch ( $error->getCode() ) {
            case System_Core_SetupException::SiteConfigNotFound:
                $this->view->setSlot( 'page_title', $this->tr( 'Server Not Configured' ) );
                $this->infoMessage = $this->tr( 'This WebIssues Server has not been configured yet.' );
                if ( !$this->request->isRelativePathUnder( '/mobile' ) ) {
                    $this->linkMessage = $this->tr( 'Go to the %1 page to configure this server.', null,
                        $this->link( '/admin/setup/install.php', $this->tr( 'Server Configuration' ) ) );
                }
                break;

            case System_Core_SetupException::DatabaseNotCompatible:
                $this->view->setSlot( 'page_title', $this->tr( 'Wrong Database Version' ) );
                $this->view->setSlot( 'header_class', 'error' );
                $this->infoMessage = $this->tr( 'Current version of the database is not compatible with this version of WebIssues Server.' );
                break;

            case System_Core_SetupException::DatabaseNotUpdated:
                $this->view->setSlot( 'page_title', $this->tr( 'Database Not Updated' ) );
                $this->infoMessage = $this->tr( 'The database of this WebIssues Server has not been updated yet.' );
                if ( !$this->request->isRelativePathUnder( '/mobile' ) ) {
                    $this->linkMessage = $this->tr( 'Go to the %1 page to update the database.', null,
                        $this->link( '/admin/setup/update.php', $this->tr( 'Server Update' ) ) );
                }
                break;
        }
   }
}
