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
* Escape special HTML characters in object properties and methods.
*
* This class wraps an object to provide escaping to its properties and return
* values of its methods.
*/
class System_Web_ObjectEscaper extends System_Web_Escaper
{
    /**
    * Constructor.
    * @param $value The value to wrap.
    */
    public function __construct( $value )
    {
        parent::__construct( $value );
    }

    /**
    * Overloading method for calling methods of the wrapped object.
    */
    public function __call( $method, $args )
    {
        $value = call_user_func_array( array( $this->value, $method ), $args );
        return self::wrap( $value );
    }

    /**
    * Overloading method for accessing properties of the wrapped object.
    */
    public function __get( $key )
    {
        return self::wrap( $this->value->$key );
    }

    /**
    * Overloading method for accessing properties of the wrapped object.
    * This method throws an exception because a wrapped object cannot be modified.
    */
    public function __set( $key, $value )
    {
        throw new System_Core_Exception( 'Cannot set value' );
    }

    /**
    * Overloading method for accessing properties of the wrapped object.
    */
    public function __isset( $key )
    {
        return isset( $this->value->$key );
    }

    /**
    * Overloading method for accessing properties of the wrapped object.
    * This method throws an exception because a wrapped object cannot be modified.
    */
    public function __unset( $key )
    {
        throw new System_Core_Exception( 'Cannot unset value' );
    }
}
