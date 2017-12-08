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

if ( !defined( 'E_RECOVERABLE_ERROR' ) )
    define( 'E_RECOVERABLE_ERROR', 4096 );
if ( !defined( 'E_DEPRECATED' ) )
    define( 'E_DEPRECATED', 8192 );
if ( !defined( 'E_USER_DEPRECATED' ) )
    define( 'E_USER_DEPRECATED', 16384 );

/**
* Exception wrapping a PHP error or warning.
*/
class System_Core_ErrorException extends System_Core_Exception
{
    private $errno = null;

    /**
    * Create an exception from specified error.
    * @param $errno Severity of the error.
    * @param $message The error message.
    * @param $file Name of the file where the error occurred.
    * @param $line The line in which the error occurred.
    */
    public function __construct( $errno, $message, $file, $line )
    {
        parent::__construct( $message );
        $this->errno = $errno;
        $this->file = $file;
        $this->line = $line;
    }

    /**
    * Return the severity of the error.
    * Severity is represented by built-in constants such as @c E_WARNING.
    */
    public function getErrno()
    {
        return $this->errno;
    }

    // inherited from System_Core_Exception
    protected function createLogString( $exception )
    {
        return sprintf( '%s: %s in %s on line %d', self::getErrorType( $this->errno ),
            $this->message, $this->file, $this->line );
    }

    /**
    * Return the last fatal error as a System_Core_ErrorException.
    * This function is only supported in PHP 5.2 and later. If no fatal error
    * occurred or the information is not available, @c null is returned.
    * This function is used by System_Core_Application::shutdown() to detect
    * and handle parsing and compilation errors which are otherwise impossible
    * to handle.
    */
    public static function getLastFatalError()
    {
        if ( !function_exists( 'error_get_last' ) )
            return null;

        $error = error_get_last();
        if ( $error != null ) {
            $errno = $error[ 'type' ];
            if ( $errno == E_ERROR || $errno == E_PARSE || $errno == E_COMPILE_ERROR ) {
                return new System_Core_ErrorException( $errno, $error[ 'message' ],
                    $error[ 'file' ], $error[ 'line' ] );
            }
        }

        return null;
    }

    private static function getErrorType( $errno )
    {
        static $types = array( E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parse error',
            E_NOTICE => 'Notice', E_CORE_ERROR => 'Core error', E_CORE_WARNING => 'Core warning',
            E_COMPILE_ERROR => 'Compile error', E_COMPILE_WARNING => 'Compile warning',
            E_USER_ERROR => 'User error', E_USER_WARNING => 'User warning', E_USER_NOTICE => 'User notice',
            E_STRICT => 'Strict warning', E_RECOVERABLE_ERROR => 'Recoverable fatal error',
            E_DEPRECATED => 'Deprecated warning', E_USER_DEPRECATED => 'User deprecated warning' );

        return isset( $types[ $errno ] ) ? 'PHP ' . $types[ $errno ] : 'Unknown PHP Error';
    }
}
