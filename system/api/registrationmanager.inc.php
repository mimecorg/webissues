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
* Manage user registration requests.
*/
class System_Api_RegistrationManager extends System_Api_Base
{
    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Generate a random activation key.
    */
    public function generateKey()
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $len = strlen( $chars );

        $result = '';

        for ( $i = 0; $i < 8; $i ++ )
            $result .= $chars[ mt_rand( 0, $len - 1 ) ];

        return $result;
    }

    /**
    * Return the registration request with given identifier.
    * @param $requestId Identifier of the request.
    * @return Array containing the request.
    */
    public function getRequest( $requestId )
    {
        $query = 'SELECT request_id, user_login, user_name, user_email, user_passwd, request_key, created_time, is_active, is_sent FROM {register_requests} WHERE request_id = %d AND is_active = 1';

        if ( !( $request = $this->connection->queryRow( $query, $requestId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownRequest );

        return $request;
    }

    /**
    * Return the registration request with given activation key.
    * @param $key The activation key.
    * @return Array containing the request.
    */
    public function getRequestWithKey( $key )
    {
        $query = 'SELECT request_id, user_login, user_name, user_email, user_passwd, request_key, created_time, is_active, is_sent FROM {register_requests} WHERE request_key = %s';

        if ( !( $request = $this->connection->queryRow( $query, $key ) ) )
            throw new System_Api_Error( System_Api_Error::InvalidActivationKey );

        $serverManager = new System_Api_ServerManager();
        $lifetime = $serverManager->getSetting( 'register_max_lifetime' );

        if ( $request[ 'created_time' ] < time() - $lifetime )
            throw new System_Api_Error( System_Api_Error::InvalidActivationKey );

        return $request;
    }

    /**
    * Get the total number of requests.
    */
    public function getRequestsCount()
    {
        $query = 'SELECT COUNT(*) FROM {register_requests} WHERE is_active = 1';

        return $this->connection->queryScalar( $query, System_Const::NoAccess );
    }

    /**
    * Get a paged list of requests.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing requests.
    */
    public function getRequestsPage( $orderBy, $limit, $offset )
    {
        $query = 'SELECT request_id, user_login, user_name, user_email, created_time FROM {register_requests} WHERE is_active = 1';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getRequestsColumns()
    {
        return array(
            'name' => 'user_name COLLATE LOCALE',
            'login' => 'user_login COLLATE LOCALE',
            'email' => 'user_email COLLATE LOCALE',
            'date' => 'created_time'
            );
    }

    /**
    * Add a request to register a new account.
    * @param $login The login of the user.
    * @param $name The name of the user.
    * @param $password The password of the user.
    * @param $email The email address of the user.
    * @param $key The activation key.
    * @return Identifier of the request.
    */
    public function addRequest( $login, $name, $password, $email, $key )
    {
        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'users' );

        try {
            $query = 'SELECT user_id FROM {users} WHERE user_login = %s OR user_name = %s';
            if ( $this->connection->queryScalar( $query, $login, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'SELECT request_id FROM {register_requests} WHERE user_login = %s OR user_name = %s';
            if ( $this->connection->queryScalar( $query, $login, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'SELECT user_id FROM {preferences} WHERE pref_key = %s AND UPPER( pref_value ) = %s';
            if ( $this->connection->queryScalar( $query, 'email', mb_strtoupper( $email ) ) !== false )
                throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );

            $query = 'SELECT request_id FROM {register_requests} WHERE UPPER( user_email ) = %s';
            if ( $this->connection->queryScalar( $query, mb_strtoupper( $email ) ) !== false )
                throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );

            $passwordHash = new System_Core_PasswordHash();
            $hash = $passwordHash->hashPassword( $password );

            $query = 'INSERT INTO {register_requests} ( user_login, user_name, user_email, user_passwd, request_key, created_time, is_active, is_sent ) VALUES ( %s, %s, %s, %s, %s, %d, 0, 0 )';
            $this->connection->execute( $query, $login, $name, $email, $hash, $key, time() );

            $requestId = $this->connection->getInsertId( 'register_requests', 'request_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Access, System_Api_EventLog::Information,
            $eventLog->tr( 'User "%1" registered', null, $name ) );

        return $requestId;
    }

    /**
    * Activate the registration request.
    * @param $request The request to activate.
    * @return @c true if the request was activated.
    */
    public function activateRequest( $request )
    {
        $requestId = $request[ 'request_id' ];
        $isActive = $request[ 'is_active' ];

        if ( $isActive == true )
            return false;

        $query = 'UPDATE {register_requests} SET is_active = 1 WHERE request_id = %d';
        $this->connection->execute( $query, $requestId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Access, System_Api_EventLog::Information,
            $eventLog->tr( 'Registration request for user "%1" activated', null, $request[ 'user_name' ] ) );

        return true;
    }

    /**
    * Approve the registration request.
    * @param $request The request to approve.
    * @return @c Identifier of the user.
    */
    public function approveRequest( $request )
    {
        $requestId = $request[ 'request_id' ];
        $login = $request[ 'user_login' ];
        $name = $request[ 'user_name' ];
        $email = $request[ 'user_email' ];
        $hash = $request[ 'user_passwd' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'users' );

        try {
            $query = 'SELECT user_id FROM {users} WHERE user_login = %s OR user_name = %s';
            if ( $this->connection->queryScalar( $query, $login, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'INSERT INTO {users} ( user_login, user_name, user_passwd, user_access, passwd_temp ) VALUES ( %s, %s, %s, %d, %d )';
            $this->connection->execute( $query, $login, $name, $hash, System_Const::NormalAccess, 0 );

            $userId = $this->connection->getInsertId( 'users', 'user_id' );

            $query = 'INSERT INTO {preferences} ( user_id, pref_key, pref_value ) VALUES ( %d, %s, %s )';
            $this->connection->execute( $query, $userId, 'email', $email );

            $query = 'DELETE FROM {register_requests} WHERE request_id = %d';
            $this->connection->execute( $query, $requestId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Registration request for user "%1" approved', null, $name ) );

        return $userId;
    }

    /**
    * Reject the registration request.
    * @param $request The request to reject.
    */
    public function rejectRequest( $request )
    {
        $requestId = $request[ 'request_id' ];
        $name = $request[ 'user_name' ];

        $query = 'DELETE FROM {register_requests} WHERE request_id = %d';
        $this->connection->execute( $query, $requestId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Registration request for user "%1" rejected', null, $name ) );

        return $userId;
    }

    /**
    * Remove expired registration requests that were not activated.
    */
    public function expireRequests()
    {
        $query = 'DELETE FROM {register_requests} WHERE is_active = 0 AND created_time < %d';

        $serverManager = new System_Api_ServerManager();
        $lifetime = $serverManager->getSetting( 'register_max_lifetime' );

        $this->connection->execute( $query, time() - $lifetime );
    }

    public function getRequestsToEmail()
    {
        $query = 'SELECT request_id, user_login, user_name, user_email, created_time FROM {register_requests} WHERE is_active = 1 AND is_sent = 0';

        return $this->connection->queryTable( $query );
    }

    public function setRequestsMailed()
    {
        $query = 'UPDATE {register_requests} SET is_sent = 1 WHERE is_active = 1 AND is_sent = 0';

        $this->connection->execute( $query );
    }
}
