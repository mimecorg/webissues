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

class Common_Errors_Helper extends System_Web_Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getRequestUrl()
    {
        // Apache style error page
        $url = $this->request->getRequestUrl();
        if ( $url != null )
            return $url;

        // IIS style error page
        $query = $this->request->getRawQueryString();
        if ( $query != null ) {
            $parts = explode( ';', $query, 2 );
            if ( isset( $parts[ 1 ] ) )
                return $parts[ 1 ];
        }

        return null;
    }
}
