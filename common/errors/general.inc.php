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

class Common_Errors_General extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_MessageBlock' );
        $this->view->setSlot( 'page_title', $this->tr( 'Unexpected Error' ) );
        $this->view->setSlot( 'header_class', 'error' );

        $exception = System_Core_Application::getInstance()->getFatalError();
        if ( is_a( $exception, 'System_Api_Error' ) ) {
            $helper = new System_Web_ErrorHelper();
            $this->errorMessage = $helper->getErrorMessage( $exception->getMessage() );
        }
    }
}
