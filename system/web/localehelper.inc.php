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

if ( !defined( 'WI_VERSION' ) ) die( -1 );

/**
* Class providing month names and other localized information.
*/
class System_Web_LocaleHelper extends System_Web_Base
{
    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Format file size using appropriate unit.
    * @param $size File size in bytes.
    * @return File size in bytes, kilobytes or megabytes.
    */
    public function formatFileSize( $size )
    {
        $formatter = new System_Api_Formatter();

        if ( $size < 1024 )
            return $this->t( "text.bytes", array( $formatter->formatDecimalNumber( $size, 0 ) ) );

        $size /= 1024;
        if ( $size < 1024 )
            return $this->t( "text.kB", array( $formatter->formatDecimalNumber( $size, 1, System_Api_Formatter::StripZeros ) ) );

        $size /= 1024;
        return $this->t( "text.MB", array( $formatter->formatDecimalNumber( $size, 1, System_Api_Formatter::StripZeros ) ) );
    }
}
