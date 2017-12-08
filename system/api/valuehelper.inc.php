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
* Helper functions for handling values in internal format.
*/
class System_Api_ValueHelper
{
    /**
    * Return @c true if the given attribute values are equal. Attributes are
    * compared according to their type.
    * @param $definition The definition of the attribute type.
    * @param $value1 The first value to compare.
    * @param $value2 The second value to compare.
    */
    public static function areAttributeValuesEqual( $definition, $value1, $value2 )
    {
        if ( $value1 == '' && $value2 == '' )
            return true;

        if ( $value1 == '' || $value2 == '' )
            return false;

        $info = System_Api_DefinitionInfo::fromString( $definition );

        switch ( $info->getType() ) {
            case 'TEXT':
            case 'ENUM':
            case 'USER':
                return !strcmp( $value1, $value2 );

            case 'NUMERIC':
                return (float)$value1 == (float)$value2;

            case 'DATETIME':
                return new DateTime( $value1, new DateTimeZone( 'UTC' ) ) == new DateTime( $value2, new DateTimeZone( 'UTC' ) );

            default:
                throw new System_Core_Exception( 'Invalid attribute type' );
        }
    }
}
