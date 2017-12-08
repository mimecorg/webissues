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
* Wrapper for PHP session handling mechanism.
*
* This class implements a session handler and provides access to session data.
* It should be used instead of directly accessing $_SESSION.
*
* The custom session handler uses System_Api_SessionManager to store sessions
* in the database. It also uses the PHP garbage collection mechanism to call
* System_Core_Application::collectGarbage() every 100th request. See also
* http://php.net/session_set_save_handler.
* 
* An instance of this class is accessible through the System_Core_Application
* object.
*/
class System_Core_Session
{
    private $started = false;
    private $valid = false;
    private $initialized = false;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Initialize session parameters and open existing session if a valid cookie
    * was passed. Note that a new session is not automatically created until
    * createSession() is explicitly called.
    */
    public function initialize()
    {
        ini_set( 'session.use_cookies', 1 );
        ini_set( 'session.use_only_cookies', 1 );
        ini_set( 'session.use_trans_sid', 0 );

        // make sure session id is 32 characters long
        ini_set( 'session.hash_function', 1 ); // SHA-1, 160 bits
        ini_set( 'session.hash_bits_per_character', 5 );

        $serverManager = new System_Api_ServerManager();
        $divisor = $serverManager->getSetting( 'gc_divisor' );

        if ( $divisor == 0 ) {
            ini_set( 'session.gc_probability', 0 );
        } else {
            ini_set( 'session.gc_probability', 1 );
            ini_set( 'session.gc_divisor', $divisor );
        }

        session_name( 'WebIssuesSID' );

        $url = parse_url( WI_BASE_URL );
        // cookie is valid within the whole base directory
        $path = isset( $url[ 'path' ] ) ? $url[ 'path' ] . '/' : '/';
        // same domain as originator is assumed (this works for localhost, IP addresses, etc.)
        $domain = '';
        // don't send cookie over usecured connection if HTTPS is used
        $secure = ( $url[ 'scheme' ] == 'https' );

        session_set_cookie_params( 0, $path, $domain, $secure );

        // session is not implicitly started unless the cookie is passed
        if ( isset( $_COOKIE[ session_name() ] ) ) {
            $this->createSession();

            // destroy session if it doesn't exist or has expired
            if ( !$this->valid )
                $this->destroySession();
        }
    }

    /**
    * Close the session and write all data back to database.
    */
    public function close()
    {
        if ( $this->started ) {
            session_write_close();
            $this->started = false;
        }
    }

    /**
    * Create a new session. If a session is already opened, it is destroyed
    * and a new empty session with a new identifier is created.
    */
    public function createSession()
    {
        if ( $this->started ) {
            session_regenerate_id( true );
            return;
        }

        session_set_save_handler(
            array( $this, 'handlerOpen' ), array( $this, 'handlerClose' ),
            array( $this, 'handlerRead' ), array( $this, 'handlerWrite' ),
            array( $this, 'handlerDestroy' ), array( $this, 'handlerGc' ) );

        session_start();

        if ( $this->initialized )
            session_regenerate_id( false );

        $this->started = true;
        $this->initialized = true;
    }

    /**
    * Destroy the session and delete the cookie.
    */
    public function destroySession()
    {
        if ( !$this->started )
            return;

        if ( isset( $_COOKIE[ session_name() ] ) ) {
            // emit the session cookie with date in the past to delete it
            $params = session_get_cookie_params();
            setrawcookie( session_name(), session_id(), 1, $params[ 'path' ], $params[ 'domain' ], $params[ 'secure' ] );
        }

        $_SESSION = array();
        session_destroy();

        $this->started = false;
    }

    /**
    * Return the value of a specific session variable.
    * @param $key Name of the variable to return.
    * @param $default The default value if the variable was not set.
    */
    public function getValue( $key, $default = null )
    {
        return isset( $_SESSION[ $key ] ) ? $_SESSION[ $key ] : $default;
    }

    /**
    * Set the value of a specific session variable.
    * @param $key Name of the variable to set.
    * @param $value The value of the variable. It should be a simple
    * serializable data type.
    */
    public function setValue( $key, $value )
    {
        $_SESSION[ $key ] = $value;
    }

    /**
    * Clear the value of a specific session variable.
    * @param $key Name of the variable to clear.
    */
    public function clearValue( $key )
    {
        unset( $_SESSION[ $key ] );
    }

    /**
    * Return the value of a specific cookie.
    * @param $key Name of the cookie to return.
    * @param $default The default value if the cookie does not exist.
    */
    public function getCookie( $key, $default = null )
    {
        return isset( $_COOKIE[ $key ] ) ? $_COOKIE[ $key ] : $default;
    }

    /**
    * Set the value of a specific cookie.
    * @param $key Name of the cookie to set.
    * @param $value The value of the cookie.
    * @param $days The number of days after which cookie expires (default value
    * of 0 means cookie expires when browser is closed).
    */
    public function setCookie( $key, $value, $days = 0 )
    {
        $params = session_get_cookie_params();
        $expires = ( $days != 0 ) ? time() + $days * 86400 : 0;
        setrawcookie( $key, $value, $expires, $params[ 'path' ], $params[ 'domain' ], $params[ 'secure' ] );
    }

    /**
    * Return the path used for the cookies.
    */
    public function getCookiePath()
    {
        $params = session_get_cookie_params();
        return $params[ 'path' ];
    }

    /**
    * Return @c true if the cookie is secure.
    */
    public function isCookieSecure()
    {
        $params = session_get_cookie_params();
        return $params[ 'secure' ];
    }

    /**
    * Implementation of the open callback.
    */
    public function handlerOpen( $savePath, $sessionName )
    {
        return true;
    }

    /**
    * Implementation of the close callback.
    */
    public function handlerClose()
    {
        return true;
    }

    /**
    * Implementation of the read callback.
    */
    public function handlerRead( $id )
    {
        $sessionManager = new System_Api_SessionManager();

        $data = '';
        $this->valid = $sessionManager->readSession( $id, $data );

        return $data;
    }

    /**
    * Implementation of the write callback.
    */
    public function handlerWrite( $id, $data )
    {
        if ( $this->started ) {
            $sessionManager = new System_Api_SessionManager();
            $sessionManager->writeSession( $id, $data );
        }
        return true;
    }

    /**
    * Implementation of the destroy callback.
    */
    public function handlerDestroy( $id )
    {
        if ( $this->started ) {
            $sessionManager = new System_Api_SessionManager();
            $sessionManager->deleteSession( $id );
        }
        return true;
    }

    /**
    * Implementation of the gc callback.
    */
    public function handlerGc( $lifetime )
    {
        System_Core_Application::getInstance()->collectGarbage();
        return true;
    }
}
