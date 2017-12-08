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
* Escape special HTML characters in array elements.
*
* This class wraps an array to provide escaping to its elements. It implements
* the standard Iterator, ArrayAccess and Countable interfaces so that the object
* behaves like a regular array, including using the @c foreach statement,
* square brackets and the %count() function.
*/
class System_Web_ArrayEscaper extends System_Web_Escaper implements Iterator, ArrayAccess, Countable
{
    private $count;

    /**
    * Constructor.
    * @param $value The value to wrap.
    */
    public function __construct( $value )
    {
        parent::__construct( $value );
    }

    /**
    * Implementation of the Iterator interface.
    */
    public function rewind()
    {
        reset( $this->value );
        $this->count = count( $this->value );
    }

    /**
    * Implementation of the Iterator interface.
    */
    public function key()
    {
        return key( $this->value );
    }

    /**
    * Implementation of the Iterator interface.
    */
    public function current()
    {
        return self::wrap( current( $this->value ) );
    }

    /**
    * Implementation of the Iterator interface.
    */
    public function next()
    {
        next( $this->value );
        $this->count--;
    }

    /**
    * Implementation of the Iterator interface.
    */
    public function valid()
    {
        return $this->count > 0;
    }

    /**
    * Implementation of the ArrayAccess interface.
    */
    public function offsetExists( $offset )
    {
        return isset( $this->value[ $offset ] );
    }

    /**
    * Implementation of the ArrayAccess interface.
    */
    public function offsetGet( $offset )
    {
        return self::wrap( $this->value[ $offset ] );
    }

    /**
    * Implementation of the ArrayAccess interface.
    * This method throws an exception because a wrapped array cannot be modified.
    */
    public function offsetSet( $offset, $value )
    {
        throw new System_Core_Exception( 'Cannot set value' );
    }

    /**
    * Implementation of the ArrayAccess interface.
    * This method throws an exception because a wrapped array cannot be modified.
    */
    public function offsetUnset( $offset )
    {
        throw new System_Core_Exception( 'Cannot unset value' );
    }

    /**
    * Implementation of the Countable interface.
    */
    public function count()
    {
        return count( $this->value );
    } 
}
