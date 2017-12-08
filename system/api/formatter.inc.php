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
* Format numbers and dates according to current locale settings.
*
* This class should be used to convert locale independent values stored
* in the database into a localized representation for displaying.
* @see System_Api_Parser
*/
class System_Api_Formatter
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Allow multi-line string values. */
    const MultiLine = 1;
    /** Strip trailing zeros from the fractional part of the number. */
    const StripZeros = 2;
    /** Convert the date and time from UTC to local time zone. */
    const ToLocalTimeZone = 4;
    /*@}*/

    private $locale = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        $this->locale = new System_Api_Locale();
    }

    /**
    * Format a floating point number.
    * @param $number The @c float value to format.
    * @param $decimal The number of decimal digits to display.
    * @param $flags If StripZeros is given, trailing zeros are stripped from
    * the fractional part of the number.
    * @return The localized formatted string.
    */
    public function formatDecimalNumber( $number, $decimal, $flags = 0 )
    {
        $info = System_Api_DefinitionInfo::fromString( $this->locale->getSettingFormat( 'number_format' ) );

        $groupSeparator = $info->getMetadata( 'group-separator' );
        $decimalSeparator = $info->getMetadata( 'decimal-separator' );

        $value = number_format( $number, $decimal, $decimalSeparator, $groupSeparator );

        if ( ( $flags & self::StripZeros ) && $decimal > 0 && strpos( $value, $decimalSeparator ) !== false )
            $value = rtrim( rtrim( $value, '0' ), $decimalSeparator );

        return $value;
    }

    /**
    * Convert a floating point number to localized format.
    * @param $value The number in internal format.
    * @param $decimal The number of decimal digits to display.
    * @param $flags If StripZeros is given, trailing zeros are stripped from
    * the fractional part of the number.
    * @return The localized formatted string.
    */
    public function convertDecimalNumber( $value, $decimal, $flags = 0 )
    {
        return $this->formatDecimalNumber( (float)$value, $decimal, $flags );
    }

    /**
    * Format a date.
    * @param $stamp The time stamp representing the date.
    * @param $flags If ToLocalTimeZone is passed time is converted from UTC
    * to local time zone.
    * @return The localized formatted string.
    */
    public function formatDate( $stamp, $flags = 0 )
    {
        $date = new DateTime( "@$stamp" );

        if ( $flags & self::ToLocalTimeZone )
            $date->setTimezone( $this->getLocalTimeZone() );

        return $date->format( $this->getDateFormat() );
    }

    /**
    * Convert a date to localized format.
    * @param $value The date in internal format.
    * @return The localized formatted string.
    */
    public function convertDate( $value )
    {
        $date = new DateTime( $value, new DateTimeZone( 'UTC' ) );

        return $date->format( $this->getDateFormat() );
    }

    /**
    * Convert a time to localized format.
    * @param $value The time in internal format.
    * @return The localized formatted string.
    */
    public function convertTime( $value )
    {
        $date = new DateTime( $value, new DateTimeZone( 'UTC' ) );

        return $date->format( $this->getTimeFormat() );
    }

    /**
    * Format a date and time.
    * @param $stamp The time stamp representing the date and time.
    * @param $flags If ToLocalTimeZone is passed time is converted from UTC
    * to local time zone.
    * @return The localized formatted string.
    */
    public function formatDateTime( $stamp, $flags = 0 )
    {
        $date = new DateTime( "@$stamp" );

        if ( $flags & self::ToLocalTimeZone )
            $date->setTimezone( $this->getLocalTimeZone() );

        return $date->format( $this->getDateTimeFormat() );
    }

    /**
    * Convert a date and time to localized format.
    * @param $value The date and time in internal format.
    * @param $flags If ToLocalTimeZone is passed time is converted from UTC
    * to local time zone.
    * @return The localized formatted string.
    */
    public function convertDateTime( $value, $flags = 0 )
    {
        $date = new DateTime( $value, new DateTimeZone( 'UTC' ) );

        if ( $flags & self::ToLocalTimeZone )
            $date->setTimezone( $this->getLocalTimeZone() );

        return $date->format( $this->getDateTimeFormat() );
    }

    /**
    * Format an attribute value according to its definition.
    * @param $definition The definition of the attribute type.
    * @param $value The internal value of the attribute.
    * @param $flags If MultiLine is given, new lines and multiple spaces
    * are preserved in the value.
    * @return The localized formatted string.
    */
    public function convertAttributeValue( $definition, $value, $flags = 0 )
    {
        if ( $value == '' )
            return '';

        $info = System_Api_DefinitionInfo::fromString( $definition );

        switch ( $info->getType() ) {
            case 'TEXT':
                if ( $info->getMetadata( 'multi-line', 0 ) && ( $flags & self::MultiLine ) )
                    return $value;
                else
                    return $this->toSingleLine( $value );

            case 'ENUM':
            case 'USER':
                return $this->toSingleLine( $value );

            case 'NUMERIC':
                return $this->convertDecimalNumber( $value, $info->getMetadata( 'decimal', 0 ),
                    $info->getMetadata( 'strip', 0 ) ? self::StripZeros : 0 );

            case 'DATETIME':
                if ( $info->getMetadata( 'time', 0 ) )
                    return $this->convertDateTime( $value, $info->getMetadata( 'local', 0 ) ? self::ToLocalTimeZone : 0 );
                else
                    return $this->convertDate( $value );

            default:
                throw new System_Api_Error( System_Api_Error::InvalidDefinition );
        }
    }

    private function getDateFormat()
    {
        $info = System_Api_DefinitionInfo::fromString( $this->locale->getSettingFormat( 'date_format' ) );

        $order = $info->getMetadata( 'date-order' );
        $separator = $info->getMetadata( 'date-separator' );

        $parts[ 'y' ] = 'Y';
        $parts[ 'm' ] = $info->getMetadata( 'pad-month' ) ? 'm' : 'n';
        $parts[ 'd' ] = $info->getMetadata( 'pad-day' ) ? 'd' : 'j';

        return $parts[ $order[ 0 ] ] . $separator . $parts[ $order[ 1 ] ] . $separator . $parts[ $order[ 2 ] ];
    }

    private function getTimeFormat()
    {
        $info = System_Api_DefinitionInfo::fromString( $this->locale->getSettingFormat( 'time_format' ) );

        $separator = $info->getMetadata( 'time-separator' );

        if ( $info->getMetadata( 'time-mode' ) == 12 )
            return ( $info->getMetadata( 'pad-hour' ) ? 'h' : 'g' ) . $separator . 'i a';
        else
            return ( $info->getMetadata( 'pad-hour' ) ? 'H' : 'G' ) . $separator . 'i';
    }

    private function getDateTimeFormat()
    {
        return $this->getDateFormat() . ' ' . $this->getTimeFormat();
    }

    private function getLocalTimeZone()
    {
        return new DateTimeZone( $this->locale->getSetting( 'time_zone' ) );
    }

    private function toSingleLine( $value )
    {
        return preg_replace( '/[ \t\n]+/', ' ', trim( $value, " \t\n" ) );
    }
}
