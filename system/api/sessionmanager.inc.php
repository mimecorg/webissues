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
* Manage user sessions.
*
* This class implements the session storage mechanism using the database.
*
* @see System_Core_Session
*/
class System_Api_SessionManager extends System_Api_Base
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Administrator access is required. */
    const RequireAdministrator = 1;
    /*@}*/

    private static $currentSession = null;
    private static $user = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Authenticate a user and create a session. A System_Api_Error exception
    * is thrown when login or password is incorrect, access was disabled for
    * the user or when the password is temporary and no new password is
    * provided.
    * @param $login Login name of the user.
    * @param $password Password of the user.
    * @param $newPassword Optional new password to set.
    * @return Array containing user details.
    */
    public function login( $login, $password, $newPassword = null )
    {
        $user = null;
        $isTemp = false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'users' );

        try {
            $query = 'SELECT user_id, user_name, user_passwd, user_access, passwd_temp FROM {users}'
                . ' WHERE user_login = %s AND user_access > %d';

            $user = $this->connection->queryRow( $query, $login, System_Const::NoAccess );

            if ( $user != null ) {
                $userId = $user[ 'user_id' ];
                $hash = $user[ 'user_passwd' ];
                $isTemp = $user[ 'passwd_temp' ];

                $passwordHash = new System_Core_PasswordHash();

                if ( $passwordHash->checkPassword( $password, $hash ) ) {
                    if ( $newPassword != null ) {
                        if ( $newPassword == $password )
                            throw new System_Api_Error( System_Api_Error::CannotReusePassword );

                        if ( System_Core_Application::getInstance()->getSite()->getConfig( 'demo_mode' ) ) {
                            if ( $user[ 'user_access' ] != System_Const::AdministratorAccess )
                                throw new System_Api_Error( System_Api_Error::AccessDenied );
                        }

                        $newHash = $passwordHash->hashPassword( $newPassword );

                        $query = 'UPDATE {users} SET user_passwd = %s, passwd_temp = 0 WHERE user_id = %d';
                        $this->connection->execute( $query, $newHash, $userId );

                        $isTemp = false;
                    } else if ( $passwordHash->isNewHashNeeeded( $hash ) ) {
                        $newHash = $passwordHash->hashPassword( $password );

                        $query = 'UPDATE {users} SET user_passwd = %s WHERE user_id = %d';
                        $this->connection->execute( $query, $newHash, $userId );
                    }
                } else {
                    $user = null;
                }
            }

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        if ( $user != null && $isTemp ) {
            $this->logout();
            throw new System_Api_Error( System_Api_Error::MustChangePassword );
        }

        $this->loginCommon( $login, $user );

        return $user;
    }

    /**
    * Check access for a user without creating the session.
    * @param $login Login name of the user.
    * @param $password Password of the user.
    * @param $flags If RequireAdministrator is passed an error is thrown
    * if the user does not have administrator access to the system.
    * @return Array containing user details.
    */
    public function checkAccess( $login, $password, $flags = 0 )
    {
        $query = 'SELECT user_id, user_name, user_passwd, user_access FROM {users}'
            . ' WHERE user_login = %s AND user_access > %d';

        $user = $this->connection->queryRow( $query, $login, System_Const::NoAccess );

        if ( $user == null )
            throw new System_Api_Error( System_Api_Error::IncorrectLogin );

        $passwordHash = new System_Core_PasswordHash();

        if ( !$passwordHash->checkPassword( $password, $user[ 'user_passwd' ] ) )
            throw new System_Api_Error( System_Api_Error::IncorrectLogin );

        if ( $flags & self::RequireAdministrator && $user[ 'user_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $user;
    }

    /**
    * Create a session for the specified user without checking password.
    * @param $login Login name of the user.
    * @return Array containing user details.
    */
    public function loginAs( $login )
    {
        $query = 'SELECT user_id, user_name, user_access FROM {users}'
            . ' WHERE user_login = %s';

        $user = $this->connection->queryRow( $query, $login );

        $this->loginCommon( $login, $user );

        return $user;
    }

    /**
    * Create a System_Api_Principal based on the current session.
    */
    public function initializePrincipal()
    {
        $principal = new System_Api_Principal( self::$user );
        System_Api_Principal::setCurrent( $principal );
    }

    private function loginCommon( $login, $user )
    {
        $eventLog = new System_Api_EventLog( $this );

        if ( !$user ) {
            $this->logout();

            $eventLog->addEvent( System_Api_EventLog::Access, System_Api_EventLog::Error,
                $eventLog->tr( 'Incorrect login attempt for user "%1"', null, $login ) );

            throw new System_Api_Error( System_Api_Error::IncorrectLogin );
        }

        self::$user = array();
        self::$user[ 'user_id' ] = $user[ 'user_id' ];
        self::$user[ 'user_name' ] = $user[ 'user_name' ];
        self::$user[ 'user_access' ] = $user[ 'user_access' ];

        $this->initializePrincipal();

        $session = System_Core_Application::getInstance()->getSession();
        $session->createSession();

        $eventLog->addEvent( System_Api_EventLog::Access, System_Api_EventLog::Information,
            $eventLog->tr( 'Session opened for user "%1"', null, $login ) );
    }

    /**
    * Log out the user and destroy the session.
    */
    public function logout()
    {
        self::$user = null;
        $this->initializePrincipal();

        $session = System_Core_Application::getInstance()->getSession();
        $session->destroySession();
    }

    /**
    * Read existing session data from the database.
    * This method is used internally by System_Core_Session.
    */
    public function readSession( $id, &$data )
    {
        $query = 'SELECT s.session_id, s.session_data, s.last_access, u.user_id, u.user_name, u.user_access'
            . ' FROM {sessions} AS s'
            . ' JOIN {users} AS u ON u.user_id = s.user_id'
            . ' WHERE s.session_id = %s';

        $session = $this->connection->queryRow( $query, $id );
        if ( !$session )
            return false;

        self::$currentSession = $session;

        $serverManager = new System_Api_ServerManager();
        $lifetime = $serverManager->getSetting( 'session_max_lifetime' );

        if ( $session[ 'last_access' ] < time() - $lifetime )
            return false;

        self::$user = array();
        self::$user[ 'user_id' ] = $session[ 'user_id' ];
        self::$user[ 'user_name' ] = $session[ 'user_name' ];
        self::$user[ 'user_access' ] = $session[ 'user_access' ];

        $data = $session[ 'session_data' ];

        return true;
    }

    /**
    * Store session data in the database.
    * This method is used internally by System_Core_Session.
    */
    public function writeSession( $id, $data )
    {
        if ( self::$currentSession[ 'session_id' ] == $id && self::$currentSession[ 'session_data' ] == $data && ( time() - self::$currentSession[ 'last_access' ] ) < 15 )
            return;

        if ( self::$currentSession[ 'session_id' ] == $id )
            $query = 'UPDATE {sessions} SET session_data = %3s, last_access = %4d WHERE session_id = %1s';
        else
            $query = 'INSERT INTO {sessions} ( session_id, user_id, session_data, last_access ) VALUES ( %1s, %2d, %3s, %4d )';

        $this->connection->execute( $query, $id, self::$user[ 'user_id' ], $data, time() );
    }

    /**
    * Delete the given session from the database.
    * This method is used internally by System_Core_Session.
    */
    public function deleteSession( $id )
    {
        if ( self::$currentSession[ 'session_id' ] == $id ) {
            $query = 'DELETE FROM {sessions} WHERE session_id = %s';
            $this->connection->execute( $query, $id );
        }
    }

    /**
    * Remove expired sessions from the database. The lifetime of sessions
    * can be configured in server settings.
    */
    public function expireSessions()
    {
        $query = 'DELETE FROM {sessions} WHERE last_access < %d';

        $serverManager = new System_Api_ServerManager();
        $lifetime = $serverManager->getSetting( 'session_max_lifetime' );

        $this->connection->execute( $query, time() - $lifetime );
    }
}
