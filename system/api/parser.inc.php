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
* Parse numbers and dates according to current locale settings.
*
* This class extends the functionality of the System_Api_Validator with
* support for parsing and validating localized values. All methods of this
* class except normalizeString() expect the string to be already normalized.
* All methods throw a System_Api_Error exception if validation fails.
* @see System_Api_Formatter
*/
class System_Api_Parser extends System_Api_Validator
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Convert the date and time from local time zone to UTC. */
    const FromLocalTimeZone = 4;
    /*@}*/

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Normalize the string, removing unnecessary spaces, and validate it.
    * @param $string The string to normalize.
    * @param $maxLength The maximum allowed length of the string.
    * @param $flags If AllowEmpty is given, value is not required. If MultiLine
    * is given, value can be a multi-line string.
    */
    public function normalizeString( $string, $maxLength = null, $flags = 0 )
    {
        if ( $flags & self::MultiLine ) {
            $string = str_replace( "\r\n", "\n", $string );
            $string = rtrim( $string, " \n\t" );
        } else {
            $string = trim( $string, ' ' );
            if ( strpos( $string, '  ' ) !== false )
                $string = preg_replace( '/  +/', ' ', $string );
        }

        parent::checkString( $string, $maxLength, $flags );

        return $string;
    }

    /**
    * Parse and validate a localized decimal number.
    * @param $value The normalized string to parse.
    * @param $decimal The maximum allowed number of decimal digits.
    * @return The number as a @c float value.
    */
    public function parseDecimalNumber( $value, $decimal )
    {
        $info = System_Api_DefinitionInfo::fromString( $this->locale->getSettingFormat( 'number_format' ) );

        $groupSeparator = $info->getMetadata( 'group-separator' );
        if ( $groupSeparator != '' )
            $pattern = '(-?\d\d?\d?(?:' . preg_quote( $groupSeparator, '/' ) . '\d\d\d)+|-?\d+)';
        else
            $pattern = '(-?\d+)';

        $pattern .= '(?:' . preg_quote( $info->getMetadata( 'decimal-separator' ), '/' ) . '(\d*))?';

        if ( !preg_match( '/^' . $pattern . '$/', $value, $matches ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        $fractionPart = !empty( $matches[ 2 ] ) ? $matches[ 2 ] : '';
        $i = strlen( $fractionPart );
        while ( $i > 0 && $fractionPart{ $i - 1 } == '0' )
            $i--;
        $fractionPart = substr( $fractionPart, 0, $i );

        if ( $i > $decimal )
            throw new System_Api_Error( System_Api_Error::TooManyDecimals );

        $integerPart = $matches[ 1 ];
        if ( $groupSeparator != '' )
            $integerPart = str_replace( $groupSeparator, '', $integerPart );

        if ( !empty( $fractionPart ) )
            $value = (float)( $integerPart . '.' . $fractionPart );
        else
            $value = (float)$integerPart;

        // make sure the number doesn't exceed 14 digits of precision
        if ( abs( $value ) >= pow( 10.0, 14 - $decimal ) )
            throw new System_Api_Error( System_Api_Error::TooManyDigits );

        return $value;
    }

    /**
    * Validate a localized decimal number and convert it to internal format.
    * @param $value The normalized string to parse.
    * @param $decimal The maximum allowed number of decimal digits.
    * @return The number in internal format.
    */
    public function convertDecimalNumber( $value, $decimal )
    {
        $number = $this->parseDecimalNumber( $value, $decimal );

        return number_format( $number, $decimal, '.', '' );
    }

    /**
    * Validate a localized date and convert it to internal format.
    * @param $value The normalized string to parse.
    * @return The date in internal format.
    */
    public function convertDate( $value )
    {
        if ( !preg_match( '/^' . $this->getDatePattern() . '$/', $value, $matches ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        $this->verifyDate( $matches );

        $date = new DateTime( '@0' );
        $date->setDate( $matches[ 'y' ], $matches[ 'm' ], $matches[ 'd' ] );

        return $date->format( 'Y-m-d' );
    }

    /**
    * Validate a localized date and time and convert it to internal format.
    * @param $value The normalized string to parse.
    * @param $flags If FromLocalTimeZone is passed time is converted from
    * local time zone to UTC.
    * @return The date and time in internal format.
    */
    public function convertDateTime( $value, $flags = 0 )
    {
        if ( !preg_match( '/^' . $this->getDateTimePattern() . '$/', $value, $matches ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        $this->verifyDate( $matches );
        $this->verifyTime( $matches );

        $date = new DateTime( '@0' );

        if ( $flags & self::FromLocalTimeZone )
            $date->setTimezone( $this->getLocalTimeZone() );

        $date->setDate( $matches[ 'y' ], $matches[ 'm' ], $matches[ 'd' ] );
        $date->setTime( $matches[ 'h' ], $matches[ 'i' ] );

        if ( $flags & self::FromLocalTimeZone )
            $date->setTimeZone( new DateTimeZone( 'UTC' ) );

        return $date->format( 'Y-m-d H:i' );
    }

    /**
    * Parse and validate an attribute value according to its definition.
    * The value is converted to a locale independent, standardized value
    * suitable for storing in the database.
    * @param $definition The definition of the attribute type.
    * @param $value The normalized string to parse.
    * @return The standardized value of the attribute.
    */
    public function convertAttributeValue( $definition, $value )
    {
        $info = System_Api_DefinitionInfo::fromString( $definition );

        if ( $value == '' ) {
            if ( $info->getMetadata( 'required', 0 ) )
                throw new System_Api_Error( System_Api_Error::EmptyValue );
            return '';
        }

        switch ( $info->getType() ) {
            case 'TEXT':
                break;

            case 'ENUM':
            case 'USER':
                if ( $info->getMetadata( 'multi-select', 0 ) )
                    $value = $this->normalizeList( $value );
                break;

            case 'NUMERIC':
                $value = $this->convertDecimalNumber( $value, $info->getMetadata( 'decimal', 0 ) );
                break;

            case 'DATETIME':
                if ( $info->getMetadata( 'time', 0 ) )
                    $value = $this->convertDateTime( $value, $info->getMetadata( 'local', 0 ) ? self::FromLocalTimeZone : 0 );
                else
                    $value = $this->convertDate( $value );
                break;

            default:
                throw new System_Api_Error( System_Api_Error::InvalidDefinition );
        }

        parent::checkAttributeValueInfo( $info, $value );

        return $value;
    }

    private function getDatePattern()
    {
        $info = System_Api_DefinitionInfo::fromString( $this->locale->getSettingFormat( 'date_format' ) );

        $parts[ 'y' ] = '(?P<y>\d\d\d\d)';
        $parts[ 'm' ] = '(?P<m>\d\d?)';
        $parts[ 'd' ] = '(?P<d>\d\d?)';

        $order = $info->getMetadata( 'date-order' );
        $separator = preg_quote( $info->getMetadata( 'date-separator' ), '/' );

        return $parts[ $order[ 0 ] ] . $separator . $parts[ $order[ 1 ] ] . $separator . $parts[ $order[ 2 ] ];
    }

    private function getTimePattern()
    {
        $info = System_Api_DefinitionInfo::fromString( $this->locale->getSettingFormat( 'time_format' ) );

        $separator = preg_quote( $info->getMetadata( 'time-separator' ), '/' );

        $pattern = '(?P<h>\d\d?)' . $separator . '(?P<i>\d\d?)';
        if ( $info->getMetadata( 'time-mode' ) == 12 )
            $pattern .= '(?: ?(?P<a>[AaPp][Mm]))?';

        return $pattern;
    }

    private function getDateTimePattern()
    {
        return $this->getDatePattern() . ' ' . $this->getTimePattern();
    }

    private function getLocalTimeZone()
    {
        return new DateTimeZone( $this->locale->getSetting( 'time_zone' ) );
    }

    private function verifyDate( $matches )
    {
        if ( !checkdate( $matches[ 'm' ], $matches[ 'd' ], $matches[ 'y' ] ) )
            throw new System_Api_Error( System_Api_Error::InvalidDate );
    }

    private function verifyTime( &$matches )
    {
        if ( !empty( $matches[ 'a' ] ) ) {
            if ( $matches[ 'h' ] < 1 || $matches[ 'h' ] > 12 )
                throw new System_Api_Error( System_Api_Error::InvalidTime );
            if ( $matches[ 'h' ] == 12 )
                $matches[ 'h' ] = 0;
            if ( strtolower( $matches[ 'a' ] ) == 'pm' )
                $matches[ 'h' ] += 12;
        } else {
            if ( $matches[ 'h' ] > 23 )
                throw new System_Api_Error( System_Api_Error::InvalidTime );
        }

        if ( $matches[ 'i' ] > 59 )
            throw new System_Api_Error( System_Api_Error::InvalidTime );
    }

    private function normalizeList( $value )
    {
        $result = array();

        $parts = explode( ',', $value );

        foreach ( $parts as $part ) {
            $part = trim( $part );
            if ( $part != '' )
                $result[] = $part;
        }

        return join( ', ', $result );
    }
}
