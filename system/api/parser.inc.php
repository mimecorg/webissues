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

}
