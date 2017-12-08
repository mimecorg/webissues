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
* Information about user executing the request.
*
* This object is usually creatd by calling
* System_Api_SessionManager::initializePrincipal(). When no session is available
* an anonymous user is automatically created with System_Const::NoAccess.
*/
class System_Api_Principal
{
    private static $current = null;

    private $userId = 0;
    private $userName = '';
    private $userAccess = System_Const::NoAccess;

    /**
    * Constructor.
    * @param $user Optional user data used to initialize the principal.
    */
    public function __construct( $user = null )
    {
        if ( $user != null ) {
            $this->userId = $user[ 'user_id' ];
            $this->userName = $user[ 'user_name' ];
            $this->userAccess = $user[ 'user_access' ];
        }
    }

    /**
    * Return the current principal. It represents the authenticated user
    * executing the request or an anonymous user.
    */
    public static function getCurrent()
    {
        if ( self::$current == null )
            self::$current = new System_Api_Principal();
        return self::$current;
    }

    /**
    * Set the current principal.
    */
    public static function setCurrent( $principal )
    {
        self::$current = $principal;
    }

    /**
    * Return the identifier of the user.
    */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
    * Return the name of the user.
    */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
    * Return the access level of the user.
    */
    public function getUserAccess()
    {
        return $this->userAccess;
    }

    /**
    * Return @c true if the user is authenticated (has valid session).
    */
    public function isAuthenticated()
    {
        return $this->userId != 0;
    }

    /**
    * Return @c true if the user has System_Const::AdministratorAccess 
    * to the server.
    */
    public function isAdministrator()
    {
        return $this->userAccess == System_Const::AdministratorAccess;
    }

    /**
    * Throw a System_Api_Error if the user is not authenticated.
    */
    public function checkAuthenticated()
    {
        if ( !$this->isAuthenticated() )
            throw new System_Api_Error( System_Api_Error::LoginRequired );
    }

    /**
    * Throw a System_Api_Error if the user is not an administrator.
    */
    public function checkAdministrator()
    {
        $this->checkAuthenticated();
        if ( !$this->isAdministrator() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );
    }

    /**
    * Throw a System_Api_Error if the user is not an administrator and
    * tries to perform an operation affecting another user.
    * @param $userId The user whom the operation is affecting.
    */
    public function checkAdministratorOrSelf( $userId )
    {
        $this->checkAuthenticated();
        if ( $this->userId != $userId && !$this->isAdministrator() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );
    }
}
