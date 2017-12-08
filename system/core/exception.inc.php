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
* The base class for all WebIssues exceptions.
*
* This class inherits the built-in Exception class.
*
* Usually it is not necessary to catch exceptions because all unhandled
* exceptions are passed to System_Core_Application::handleException()
* and an appropriate error page is displayed.
*/
class System_Core_Exception extends Exception
{
    private $wrappedException = null;

    /**
    * Constructor.
    * @param $message The error message.
    * @param $wrappedException An optional exception to be wrapped
    * by this exception.
    */
    public function __construct( $message, $wrappedException = null )
    {
        if ( $message == null && $wrappedException != null )
            $message = $wrappedException->getMessage();

        parent::__construct( $message, 0 );

        $this->wrappedException = $wrappedException;
    }

    /**
    * Return the exception details as a string suitable for logging.
    * If the exception contains a wrapped exception, the wrapped exception is
    * returned instead.
    */
    public function __toString()
    {
        $exception = ( $this->wrappedException != null ) ? $this->wrappedException : $this;
        return $this->createLogString( $exception );
    }

    /**
    * Convert the exception to a string suitable for logging.
    */
    protected function createLogString( $exception )
    {
        return sprintf( "Unhandled %s: %s in %s on line %d\nStack trace:\n%s", get_class( $exception ),
            $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString() );
    }

    /**
    * Wrap any exception in a System_Core_Exception object.
    * @param $exception The exception to wrap.
    * @return The @a $exception itself if it inherits System_Core_Exception
    * or a new System_Core_Exception object wrapping the given exception.
    */
    public static function wrapException( $exception )
    {
        if ( is_a( $exception, 'System_Core_Exception' ) )
            return $exception;
        return new System_Core_Exception( null, $exception );
    }
}
