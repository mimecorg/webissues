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

if ( !function_exists( 'version_compare' ) || version_compare( PHP_VERSION, '5.6' ) < 0 )
    exit( "WebIssues requires PHP 5.6 or newer." );

if ( !function_exists( 'mb_strlen' ) )
    exit( "WebIssues requires the mbstring extension." );

/*@{*/
/**
* @name Global Constants
* @relates System_Bootstrap
*/

/**
* Current version of WebIssues Server.
*/
define( 'WI_VERSION', '2.0.5' );

/**
* Current version of the WebIssues database schema.
*/
define( 'WI_DATABASE_VERSION', '2.0.010' );

/**
* The full physical path of the entry script.
*/
define( 'WI_SCRIPT_PATH', System_Bootstrap::getScriptPath() );

/**
* The path of the directory where WebIssues Server is installed (without trailing slash).
* It should always be used when loading or including files etc.
*/
define( 'WI_ROOT_DIR', System_Bootstrap::getRootDir() );

/**
* The URL of the entry script. It is not available when the script is run from
* command line.
*/
define( 'WI_SCRIPT_URL', System_Bootstrap::getScriptUrl() );

/**
* The URL of the WebIssues Server root directory (without trailing slash).
* It should always be used for creating hyperlinks. It is not available when
* the script is run from command line.
*/
define( 'WI_BASE_URL', System_Bootstrap::getBaseUrl() );

/**
* The URL of the WebIssues Guide.
*/
define( 'WI_GUIDE_URL', 'https://doc.mimec.org/webissues-guide/' );

/*@}*/

/**
* Implementation of the class auto-load mechanism (see http://php.net/spl_autoload_register).
*
* For the mechanism to work, file name must be the same as class name except
* all characters are converted to lowercase, underscore is replaced with path
* separator and .inc.php extension is appended.
*
* For example System_Core_Application corresponds to system/core/application.inc.php.
*
* @relates System_Bootstrap
*/
function WebIssuesAutoload( $className )
{
    $path = WI_ROOT_DIR . '/' . str_replace( '_', '/', strtolower( $className ) ) . '.inc.php';
    if ( is_readable( $path ) )
        include_once( $path );
}

spl_autoload_register( 'WebIssuesAutoload' );

/**
* Main entry point for all scripts.
*
* The bootstrap.inc.php file should be included by all scripts which are
* valid entry points (i.e. have .php extension):
*
* @code
* require_once( '../system/bootstrap.inc.php' );
* @endcode
*
* The entry script should define an application or page class and then
* call System_Bootstrap::run(). No other files have to be manually included
* because the bootstrap.inc.php implements an auto-loading mechanism.
*
* Files which are not valid entry points (i.e. have .inc.php or .html.php
* extension) should check if the boostrap.inc.php file was included
* to prevent unauthorized access:
*
* @code
* if ( !defined( 'WI_VERSION' ) ) die( -1 );
* @endcode
*/
class System_Bootstrap
{
    /**
    * Entry point for all scripts.
    *
    * This method creates the application object and calls
    * the System_Core_Application::run() method.
    *
    * @param $class Class name of the application object to create.
    * @param $parameter Optional parameter passed to the application's constructor.
    */
    public static function run( $class, $parameter = null )
    {
        $application = System_Core_Application::createInstance( $class, $parameter );
        $application->run();
    }

    /**
    * Calculate the path of the entry script. Use WI_SCRIPT_PATH instead.
    */
    public static function getScriptPath()
    {
        if ( defined( 'WI_SCRIPT_PATH' ) )
            return WI_SCRIPT_PATH;

        $path = $_SERVER[ 'SCRIPT_FILENAME' ];
        $real = realpath( $path );
        if ( $real )
            $path = $real;
        $path = str_replace( '\\', '/', $path );

        return $path;
    }

    /**
    * Calculate the path of the root directory. Use WI_ROOT_DIR instead.
    */
    public static function getRootDir()
    {
        if ( defined( 'WI_ROOT_DIR' ) )
            return WI_ROOT_DIR;

        $dir = dirname( dirname( __FILE__ ) );
        $real = realpath( $dir );
        if ( $real )
            $dir = $real;
        $dir = str_replace( '\\', '/', $dir );
        $dir = rtrim( $dir, '/' );

        return $dir;
    }

    /**
    * Calculate the URL of the entry script. Use WI_SCRIPT_URL instead.
    */
    public static function getScriptUrl()
    {
        if ( defined( 'WI_SCRIPT_URL' ) )
            return WI_SCRIPT_URL;

        if ( !isset( $_SERVER[ 'SERVER_NAME' ] ) || $_SERVER[ 'SERVER_NAME' ] === '' )
            return '';

        $schema = ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] == 'on' ) ? 'https' : 'http';
        $host = $_SERVER[ 'SERVER_NAME' ];
        $url = $schema . '://' . $host;

        if ( isset( $_SERVER[ 'SERVER_PORT' ] ) && !strpos( $host, ':' ) ) {
            $port = $_SERVER[ 'SERVER_PORT' ];
            if ( ( $schema == 'http' && $port != 80 ) || ( $schema == 'https' && $port != 443 ) )
                $url .= ':' . $port;
        }

        $path = $_SERVER[ 'SCRIPT_NAME' ];
        $path = str_replace( '\\', '/', $path );
        $path = ltrim( $path, '/' );

        if ( $path !== '' )
            $url .= '/' . $path;

        return $url;
    }

    /**
    * Calculate the base URL. Use WI_BASE_URL instead.
    */
    public static function getBaseUrl()
    {
        if ( defined( 'WI_BASE_URL' ) )
            return WI_BASE_URL;

        $url = self::getScriptUrl();
        if ( $url === '' )
            return '';

        $path_length = strlen( self::getScriptPath() ) - strlen( self::getRootDir() );
        $url = substr( $url, 0, -$path_length );

        return $url;
    }
}
