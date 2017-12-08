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
    * Return days of week from Sunday to Saturday.
    */
    public function getDaysOfWeek()
    {
        return array(
            $this->tr( 'Sunday' ),
            $this->tr( 'Monday' ),
            $this->tr( 'Tuesday' ),
            $this->tr( 'Wednesday' ),
            $this->tr( 'Thursday' ),
            $this->tr( 'Friday' ),
            $this->tr( 'Saturday' ) );
    }

    /**
    * Return abbreviated days of week.
    */
    public function getShortDaysOfWeek()
    {
        $days = array();
        foreach ( $this->getDaysOfWeek() as $day )
            $days[] = mb_substr( $day, 0, 2 );
        return $days;
    }

    /**
    * Return month names.
    */
    public function getMonths()
    {
        return array(
            $this->tr( 'January' ),
            $this->tr( 'February' ),
            $this->tr( 'March' ),
            $this->tr( 'April' ),
            $this->tr( 'May' ),
            $this->tr( 'June' ),
            $this->tr( 'July' ),
            $this->tr( 'August' ),
            $this->tr( 'September' ),
            $this->tr( 'October' ),
            $this->tr( 'November' ),
            $this->tr( 'December' ) );
    }

    /**
    * Return abbreviated month names.
    */
    public function getShortMonths()
    {
        $months = array();
        foreach ( $this->getMonths() as $month )
            $months[] = mb_substr( $month, 0, 3 );
        return $months;
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
            return $this->tr( "%1 bytes", null, $formatter->formatDecimalNumber( $size, 0 ) );

        $size /= 1024;
        if ( $size < 1024 )
            return $this->tr( "%1 kB", null, $formatter->formatDecimalNumber( $size, 1, System_Api_Formatter::StripZeros ) );

        $size /= 1024;
        return $this->tr( "%1 MB", null, $formatter->formatDecimalNumber( $size, 1, System_Api_Formatter::StripZeros ) );
    }
}
