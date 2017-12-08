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
* Parser for definition strings.
*
* Definition strings consist of a type keyword and any number of metadata
* items. Type name can consist of uppercase letters and digist. Metadata names
* can constis of lowercase letters, digist and hyphens. Metadata values can
* be integers, strings enclosed in double quotes or arrays of strings enclosed
* in curly brackets and separated with commas.
*
* Definition strings are used for storing attribute types and other definitions
* in the database and for storing information in .ini files, for example
* description of the database schema.
*/
class System_Api_DefinitionInfo
{
    private $type = null;
    private $metadata = array();

    private static $cache = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Parse the given definition. In case of an error the System_Api_Error
    * exception is thrown.
    * @param $string The string containing the definition.
    */
    public static function fromString( $string )
    {
        if ( !empty( self::$cache[ $string ] ) )
            return self::$cache[ $string ];

        $patternNumber = '-?\d+';
        $patternString = '"(?:\\\\["\\\\nt]|[^"\\\\])*"';
        $patternKey = '[a-z0-9]+(?:-[a-z0-9]+)*';

        if ( !preg_match( '/^([A-Z]+)(.*)$/', $string, $parts ) )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        $info = new System_Api_DefinitionInfo();
        $info->type = $parts[ 1 ];
        $info->metadata = array();

        $tokens = preg_split( '/(' . $patternKey . '|' . $patternNumber . '|' . $patternString . '|[ ={},])/', $parts[ 2 ], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        $state = 'start';

        foreach ( $tokens as $token ) {
            switch ( $state ) {
                case 'start':
                    if ( $token == ' ' )
                        $state = 'space';
                    else
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    break;

                case 'space':
                    if ( preg_match( '/^' . $patternKey . '$/', $token ) ) {
                        $key = $token;
                        $state = 'key';
                    } else {
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    }
                    break;

                case 'key':
                    if ( $token == '=' )
                        $state = 'eq';
                    else
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    break;

                case 'eq':
                    if ( $token == '{' ) {
                        $items = array();
                        $state = 'array';
                    } else if ( preg_match( '/^' . $patternNumber . '$/', $token ) ) {
                        $integer = (int)$token;
                        if ( $integer != $token )
                            throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                        $info->metadata[ $key ] = $integer;
                        $state = 'start';
                    } else if ( preg_match( '/^' . $patternString . '$/', $token ) ) {
                        $info->metadata[ $key ] = stripcslashes( substr( $token, 1, -1 ) );
                        $state = 'start';
                    } else {
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    }
                    break;

                case 'array':
                    if ( $token == '}' ) {
                        $info->metadata[ $key ] = $items;
                        $state = 'start';
                    } else if ( preg_match( '/^' . $patternString . '$/', $token ) ) {
                        $items[] = stripcslashes( substr( $token, 1, -1 ) );
                        $state = 'item';
                    } else {
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    }
                    break;

                case 'item':
                    if ( $token == ',' ) {
                        $state = 'comma';
                    } else if ( $token == '}' ) {
                        $info->metadata[ $key ] = $items;
                        $state = 'start';
                    } else {
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    }
                    break;

                case 'comma':
                    if ( preg_match( '/^' . $patternString . '$/', $token ) ) {
                        $items[] = stripcslashes( substr( $token, 1, -1 ) );
                        $state = 'item';
                    } else {
                        throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    }
                    break;
            }
        }

        if ( $state != 'start' )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        self::$cache[ $string ] = $info;

        return $info;
    }

    /**
    * Format the definition to a string representation.
    * @return The string containing the definition.
    */
    public function toString()
    {
        $result[] = $this->type;

        foreach ( $this->metadata as $key => $value ) {
            $result[] = " $key=";
            if ( is_int( $value ) ) {
                $result[] = $value;
            } else if ( is_array( $value ) ) {
                $escaped = array();
                foreach ( $value as $item )
                    $escaped[] = '"' . addcslashes( $item, "\"\\\n\t" ) . '"';
                $result[] = '{' . join( ',', $escaped ) . '}';
            } else {
                $result[] = '"' . addcslashes( $value, "\"\\\n\t" ) . '"';
            }
        }

        return join( '', $result );
    }

    /**
    * Set the type of the definition.
    */
    public function setType( $type )
    {
        return $this->type = $type;
    }

    /**
    * Return the type of the definition.
    */
    public function getType()
    {
        return $this->type;
    }

    /**
    * Set the value of a metadata item.
    * @param $key The key of the metadata item.
    * @param $value Value of the metadata item or @c null to remove the item.
    */
    public function setMetadata( $key, $value )
    {
        if ( $value !== null )
            $this->metadata[ $key ] = $value;
        else
            unset( $this->metadata[ $key ] );
    }

    /**
    * Return the value of a metadata item.
    * @param $key The key of the metadata item.
    * @param $default Optional default value if the item was not defined.
    * @return The value of the metadata item.
    */
    public function getMetadata( $key, $default = null )
    {
        return isset( $this->metadata[ $key ] ) ? $this->metadata[ $key ] : $default;
    }

    /**
    * Return an array containing all metadata items.
    */
    public function getAllMetadata()
    {
        return $this->metadata;
    }

    /**
    * Validate required and optional metadata items in the definition. Each
    * array should have item names as keys and types as value. Supported
    * types are:
    * - 'i' - integer
    * - 's' - string
    * - 'a' - array of strings
    * @param $requiredKeys Array of mandatory metadata items.
    * @param $optionalKeys Array of optional metadata items.
    * @return @c true if the definition contains all required items
    * and contains no unknown items.
    */
    public function checkMetadataKeys( $requiredKeys, $optionalKeys )
    {
        foreach ( $requiredKeys as $key => $type ) {
            if ( !isset( $this->metadata[ $key ] ) )
                return false;
        }

        foreach ( $this->metadata as $key => $value ) {
            if ( isset( $requiredKeys[ $key ] ) )
                $type = $requiredKeys[ $key ];
            else if ( isset( $optionalKeys[ $key ] ) )
                $type = $optionalKeys[ $key ];
            else
                return false;

            switch ( $type ) {
                case 'i':
                    if ( !is_int( $value ) )
                        return false;
                    break;
                case 's':
                    if ( !is_string( $value ) )
                        return false;
                    break;
                case 'a':
                    if ( !is_array( $value ) )
                        return false;
                    break;
                default:
                    return false;
            }
        }

        return true;
    }
}
