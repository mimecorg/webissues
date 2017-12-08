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
* Escape special HTML characters.
*
* The &lt;, &gt;, &amp; and &quot; characters are converted to &amp;lt;,
* &amp;gt;, &amp;amp; and &amp;quot; entities.
*
* All data passed from component to the view is automatically converted
* using the wrap() method to ensure displaying text correctly
* and to prevent XSS attacks.
*
* Scalar values are converted to string with special HTML characters
* escaped. Arrays and objects are wrapped in System_Web_ArrayEscaper
* and System_Web_ObjectEscaper objects which escape their elements,
* properties and return values of methods.
*
* In most cases escaping is completely transparent. The @c null value,
* boolean values and empty arrays are not escaped to make statements
* using isset(), empty() and boolean operators work.
*
* Values which should not be escaped because they contain valid HTML
* content should be wrapped in System_Web_RawValue objects by components.
* Views can access unescaped data using getRawValue()
* or System_Web_View::getRawValue() methods.
*
* Escaping doesn't affect helper objects like System_Web_Form because
* they print HTML content directly rather than returning it as a string.
*/
class System_Web_Escaper
{
    protected $value = null;

    /**
    * Constructor.
    * @param $value The value to wrap.
    */
    public function __construct( $value )
    {
        $this->value = $value;
    }

    /**
    * Return the original wrapped array or object.
    * Note that System_Web_View::getRawValue() should be used instead
    * for accessing unescaped scalar values. Alternatively the container
    * may wrap values in System_Web_RawValue to prevent escaping.
    */
    public function getRawValue()
    {
        return $this->value;
    }

    /**
    * Return the wrapped value with special HTML characters escaped.
    */
    public function __toString()
    {
        return self::escape( $this->value );
    }

    /**
    * Convert the given value to be safe for printing.
    * @param $value The value to convert.
    * @return The escaped scalar value or wrapped array or object.
    */
    public static function wrap( $value )
    {
        if ( is_null( $value ) || is_bool( $value ) )
            return $value;
        if ( is_scalar( $value ) )
            return self::escape( $value );
        if ( is_array( $value ) ) {
            if ( empty( $value ) )
                return $value;
            return new System_Web_ArrayEscaper( $value );
        }
        if ( is_object( $value ) ) {
            if ( is_a( $value, 'System_Web_RawValue' ) )
                return $value->getRawValue();
            return new System_Web_ObjectEscaper( $value );
        }
        throw new System_Core_Exception( 'Unexpected type of value to escape' );
    }

    /**
    * Escape special HTML characters in the given scalar value.
    * @param $value The value to escape.
    * @return The value with special HTML characters converted to entities.
    */
    public static function escape( $value )
    {
        return htmlspecialchars( $value );
    }
}
