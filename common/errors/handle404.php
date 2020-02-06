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

class Common_Errors_Handle404 extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $this->response->setStatus( '404 Not Found' );

        $this->view->setDecoratorClass( 'Common_Window' );
        $this->view->setSlot( 'window_size', 'small' );
        $this->view->setSlot( 'page_title', $this->t( 'title.PageNotFound' ) );

        if ( System_Core_Application::getInstance()->isLoggingEnabled() ) {
            $url = $this->request->getRequestUrl();

            if ( $url != null ) {
                $eventLog = new System_Api_EventLog( $this );
                $eventLog->addEvent( System_Api_EventLog::Access, System_Api_EventLog::Warning, $eventLog->t( 'log.PageNotFound', array( $url ) ) );
            }
        }
    }
}

System_Bootstrap::run( 'Common_Application', 'Common_Errors_Handle404' );
