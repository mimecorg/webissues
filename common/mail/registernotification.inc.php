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

class Common_Mail_RegisterNotification extends System_Web_Component
{
    private $page = null;

    protected function __construct( $page )
    {
        parent::__construct();

        $this->page = $page;
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Mail_Layout' );
        $this->view->setSlot( 'subject', $this->tr( 'WebIssues Server registration requests' ) );

        $formatter = new System_Api_Formatter();

        $this->requests = array();
        foreach ( $this->page as $row ) {
            $row[ 'date' ] = $formatter->formatDateTime( $row[ 'created_time' ], System_Api_Formatter::ToLocalTimeZone );
            $this->requests[ $row[ 'request_id' ] ] = $row;
        }
    }
}
