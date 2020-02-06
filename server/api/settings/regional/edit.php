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

class Server_Api_Settings_Regional_Edit
{
    public $access = 'admin';

    public $params = array(
        'language' => 'string',
        'timeZone' => 'string',
        'numberFormat' => 'string',
        'dateFormat' => 'string',
        'timeFormat' => 'string',
        'firstDay' => 'int'
    );

    public function run( $language, $timeZone, $numberFormat, $dateFormat, $timeFormat, $firstDay )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $language, System_Const::ValueMaxLength );
        $validator->checkLanguage( $language );
        $validator->checkString( $timeZone, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        if ( $timeZone != '' )
            $validator->checkTimeZone( $timeZone );
        $validator->checkString( $numberFormat, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        if ( $numberFormat != '' )
            $validator->checkLocaleFormat( 'number_format', $numberFormat );
        $validator->checkString( $dateFormat, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        if ( $dateFormat != '' )
            $validator->checkLocaleFormat( 'date_format', $dateFormat );
        $validator->checkString( $timeFormat, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        if ( $timeFormat != '' )
            $validator->checkLocaleFormat( 'time_format', $timeFormat );
        if ( $firstDay != null )
            $validator->checkIntegerValue( $firstDay, 0, 6 );

        $settings = array(
            'language' => $language,
            'time_zone' => $timeZone,
            'number_format' => $numberFormat,
            'date_format' => $dateFormat,
            'time_format' => $timeFormat,
            'first_day_of_week' => $firstDay
        );

        $serverManager = new System_Api_ServerManager();

        $changed = false;

        foreach ( $settings as $key => $value ) {
            if ( $serverManager->setSetting( $key, $value ) )
                $changed = true;
        }

        $result[ 'changed' ] = $changed;

        $userLanguage = System_Api_Principal::getCurrent()->getLanguage();

        $result[ 'updateLanguage' ] = $changed && $userLanguage == null;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Regional_Edit' );
