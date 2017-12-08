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

class Admin_Setup_Config extends System_Web_Component
{
    private $values = null;

    protected function __construct( $values )
    {
        parent::__construct();

        $this->values = $values;
    }

    protected function execute()
    {
        $this->config = array();
        foreach ( $this->values as $key => $value )
            $this->config[ $key ] = new System_Web_RawValue( addcslashes( $value, '\\\'' ) );

        $site = System_Core_Application::getInstance()->getSite();
        $this->siteName = new System_Web_RawValue( $site->getSiteName() );

        $this->date = date( 'Y-m-d' );
    }
}
