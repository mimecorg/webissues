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
* @name Debugging Levels
* @relates System_Core_Debug
*/
/*@{*/
/** Debug only PHP errors, warnings and exceptions. */
define( 'DEBUG_ERRORS',   1 );
/** Debug all requests with URL and duration. */
define( 'DEBUG_REQUESTS', 2 );
/** Debug commands from the client and responses. */
define( 'DEBUG_COMMANDS', 3 );
/** Debug all SQL queries. */
define( 'DEBUG_SQL',      4 );
/** Maximum available debugging level. */
define( 'DEBUG_ALL',      4 );
/*@}*/

/**
* Debugging log used mainly for development purposes.
*
* Logging is disabled by default. To enable it create a /data/site.ini file,
* for example:
* @code
* [default]
* debug_level = DEBUG_ALL
* @endcode
*
* Debugging levels are defined as global named constants to that they can be
* used in the site configuration files. The higher the level, the more
* information is logged.
*
* An instance of this class is accessible through the System_Core_Application
* object.
*/
class System_Core_Debug
{
    private $level = 0;
    private $file = null;

    private $contents = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Set the debugging level.
    */
    public function setLevel( $level )
    {
        $this->level = $level;
    }

    /**
    * Get the debugging level.
    */
    public function getLevel()
    {
        return $this->file != null ? $this->level : 0;
    }

    /**
    * Return @c true if debugging level is higher or equal the given one.
    * Always use this method before calling write().
    */
    public function checkLevel( $level )
    {
        return $this->file != null && $this->level >= $level;
    }

    /**
    * Set the path of the debugging log.
    */
    public function setFile( $file )
    {
        $this->file = $file;
    }

    /**
    * Add message to the debugging log.
    * Any number of parameters can be passed; each is written in a separate line.
    */
    public function write()
    {
        if ( $this->getLevel() > 0 ) {
            foreach ( func_get_args() as $arg ) {
                if ( is_string( $arg ) )
                    $this->contents[] = $arg;
                else
                    $this->contents[] = print_r( $arg, true );
            }
        }
    }

    /**
    * Write all messages to the log file.
    */
    public function close()
    {
        if ( !empty( $this->contents ) ) {
            $date = date( 'Y-m-d H:i:s' );

            $request = System_Core_Application::getInstance()->getRequest();
            $host = $request->getHostName();

            $contents = "[$date, $host]\n\n" . implode( '', $this->contents ) . "\n";

            @file_put_contents( $this->file, $contents, FILE_APPEND );
        }
    }
}
