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
* Wrap a value to prevent escaping.
*
* The containers can wrap values passed to the view which should not be escaped
* because they contain valid HTML content using this object.
*
* @see System_Web_Escaper
*/
class System_Web_RawValue
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
    * Return the wrapped value.
    */
    public function getRawValue()
    {
        return $this->value;
    }

    /**
    * Return the wrapped value.
    */
    public function __toString()
    {
        return $this->value;
    }
}
