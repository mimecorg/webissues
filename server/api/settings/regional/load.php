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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Settings_Regional_Load
{
    public $access = 'admin';

    public $params = array();

    public function run()
    {
        $serverManager = new System_Api_ServerManager();

        $settings[ 'language' ] = $serverManager->getSetting( 'language' );
        $settings[ 'timeZone' ] = $serverManager->getSetting( 'time_zone' );

        $settings[ 'numberFormat' ] = $serverManager->getSetting( 'number_format' );
        $settings[ 'dateFormat' ] = $serverManager->getSetting( 'date_format' );
        $settings[ 'timeFormat' ] = $serverManager->getSetting( 'time_format' );

        $firstDay = $serverManager->getSetting( 'first_day_of_week' );
        $settings[ 'firstDay' ] = $firstDay != '' ? (int)$firstDay : null;

        $result[ 'settings' ] = $settings;

        $date = new DateTime();
        $result[ 'defaultTimeZone' ] = $date->getTimezone()->getName();

        $locale = new System_Api_Locale();
        $zones = $locale->getAvailableTimeZones();

        $offsets = array();

        foreach ( $zones as $zone ) {
            $date->setTimeZone( new DateTimeZone( $zone ) );
            $offset = (int)$date->format( 'Z' );
            if ( !isset( $offsets[ $offset ] ) ) {
                $resultZone = array();
                $resultZone[ 'offset' ] = 'GMT' . $date->format( 'P (H:i)' );
                $resultZone[ 'names' ] = array();
                $offsets[ $offset ] = $resultZone;
            }
            $offsets[ $offset ][ 'names' ][] = $zone;
        }

        ksort( $offsets );

        $result[ 'timeZones' ] = array_values( $offsets );

        $resultFormats[ 'number' ] = array();

        $formats = $locale->getAvailableFormats( 'number_format' );

        foreach ( $formats as $key => $format ) {
            $info = System_Api_DefinitionInfo::fromString( $format );
            $resultFormat = array();
            $resultFormat[ 'key' ] = $key;
            $resultFormat[ 'name' ] = $this->makeSampleNumber( $info );
            $resultFormats[ 'number' ][] = $resultFormat;
        }

        $resultFormats[ 'date' ] = array();

        $formats = $locale->getAvailableFormats( 'date_format' );

        foreach ( $formats as $key => $format ) {
            $info = System_Api_DefinitionInfo::fromString( $format );
            $resultFormat = array();
            $resultFormat[ 'key' ] = $key;
            $resultFormat[ 'name' ] = $this->makeSampleDate( $info );
            $resultFormats[ 'date' ][] = $resultFormat;
        }

        $resultFormats[ 'time' ] = array();

        $formats = $locale->getAvailableFormats( 'time_format' );

        foreach ( $formats as $key => $format ) {
            $info = System_Api_DefinitionInfo::fromString( $format );
            $resultFormat = array();
            $resultFormat[ 'key' ] = $key;
            $resultFormat[ 'name' ] = $this->makeSampleTime( $info );
            $resultFormats[ 'time' ][] = $resultFormat;
        }

        $result[ 'formats' ] = $resultFormats;

        return $result;
    }

    private function makeSampleNumber( $info )
    {
        return number_format( 1000, 2, $info->getMetadata( 'decimal-separator' ),  $info->getMetadata( 'group-separator' ) );
    }

    private function makeSampleDate( $info )
    {
        $part[ 'd' ] = $info->getMetadata( 'pad-day' ) ? 'dd' : 'd';
        $part[ 'm' ] = $info->getMetadata( 'pad-month' ) ? 'mm' : 'm';
        $part[ 'y' ] = 'yyyy';
        $separator = $info->getMetadata( 'date-separator' );
        $order = $info->getMetadata( 'date-order' );
        return $part[ $order[ 0 ] ] . $separator . $part[ $order[ 1 ] ] . $separator . $part[ $order[ 2 ] ];
    }

    private function makeSampleTime( $info )
    {
        $mode = $info->getMetadata( 'time-mode' );
        $hour = ( $mode == 12 ) ? 'h' : 'H';
        if ( $info->getMetadata( 'pad-hour' ) )
            $hour .= $hour;
        $time = $hour . $info->getMetadata( 'time-separator' ) . 'mm';
        if ( $mode == 12 )
            $time .= ' tt';
        return $time;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Regional_Load' );
