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

/**
* Configuration file loader and parser.
*/
class System_Core_IniFile
{
    /**
    * Parse a configuration file using standard PHP syntax from
    * http://php.net/parse_ini_file.
    * @param $path File path relative to WI_ROOT_DIR.
    * @param $sections If @c true, the result is grouped by sections.
    * @return An associative array containing settings.
    */
    public static function parse( $path, $sections = false )
    {
        static $cache = array();

        if ( isset( $cache[ $path ] ) )
            return $cache[ $path ];

        $file = parse_ini_file( WI_ROOT_DIR . $path, $sections );

        $cache[ $path ] = $file;

        return $file;
    }

    /**
    * Parse a configuration file. A user-defined file can override common
    * settings and sections can inherit settings from other sections by
    * using a '[name : base]' notation.
    * @param $commonPath Common file path relative to WI_ROOT_DIR.
    * @param $userPath Optional user file path relative to WI_ROOT_DIR.
    * @return An associative array containing settings grouped by sections.
    */
    public static function parseExtended( $commonPath, $userPath = null )
    {
        static $cache = array();

        if ( isset( $cache[ $commonPath ] ) )
            return $cache[ $commonPath ];

        $files[ $commonPath ] = parse_ini_file( WI_ROOT_DIR . $commonPath, true );

        if ( !empty( $userPath ) && file_exists( WI_ROOT_DIR . $userPath ) )
            $files[ $userPath ] = parse_ini_file( WI_ROOT_DIR . $userPath, true );

        $result = array();

        foreach ( $files as $path => $file ) {
            foreach ( $file as $group => $values ) {
                $parts = array_map( 'trim', explode( ':', $group ) );

                if ( count( $parts ) > 2 || array_search( '', $parts ) !== false )
                    throw new System_Core_Exception( "Invalid section name in file '$path'" );

                if ( count( $parts ) == 2 ) {
                    $base = $parts[ 1 ];
                    if ( !isset( $result[ $base ] ) )
                        throw new System_Core_Exception( "Base section '$base' was not found in file '$path'" );
                    $values = array_merge( $result[ $base ], $values );
                }

                $name = $parts[ 0 ];
                if ( isset( $result[ $name ] ) )
                    $result[ $name ] = array_merge( $result[ $name ], $values );
                else
                    $result[ $name ] = $values;
            }
        }

        $cache[ $commonPath ] = $result;

        return $result;
    }

    /**
    * Parse a configuration file using raw syntax without any escaping.
    * @param $path The path of the file to load.
    * @return An associative array containing settings grouped by sections.
    */
    public static function parseRaw( $path )
    {
        static $cache = array();

        if ( isset( $cache[ $path ] ) )
            return $cache[ $path ];

        $lines = file( WI_ROOT_DIR . $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        
        $group = '';
        $result = array();

        foreach ( $lines as $number => $line ) {
            if ( $line[ 0 ] == '#' || $line[ 0 ] == ';' )
                continue;

            if ( $line[ 0 ] == '[' ) {
                if ( substr( $line, -1, 1 ) != ']' )
                    throw new System_Core_Exception( "Syntax error in file '$path' at line " . ( $number + 1 ) );

                $group = substr( $line, 1, -1 );
                $result[ $group ] = array();
            } else {
                $parts = explode( '=', $line, 2 );
                if ( count( $parts ) < 2 )
                    throw new System_Core_Exception( "Syntax error in file '$path' at line " . ( $number + 1 ) );

                $key = rtrim( $parts[ 0 ], ' ' );
                if ( $key == '' || $group == '' )
                    throw new System_Core_Exception( "Syntax error in file '$path' at line " . ( $number + 1 ) );

                $value = ltrim( $parts[ 1 ], ' ' );
                $result[ $group ][ $key ] = $value;
            }
        }

        $cache[ $path ] = $result;

        return $result;
    }
}
