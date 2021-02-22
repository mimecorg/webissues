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
    * Get a list of requests.
    * @return An array of associative arrays representing requests.
    */
    public function getRequests()
    {
        $query = 'SELECT request_id, user_login, user_name, user_email, created_time FROM {register_requests} WHERE is_active = 1 ORDER BY user_name';

        return $this->connection->queryTable( $query );
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
            $query = 'SELECT user_id FROM {users} WHERE user_name = %s';
            if ( $this->connection->queryScalar( $query, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'SELECT user_id FROM {users} WHERE user_login = %s';
            if ( $this->connection->queryScalar( $query, $login ) !== false )
                throw new System_Api_Error( System_Api_Error::LoginAlreadyExists );

            $query = 'SELECT request_id FROM {register_requests} WHERE user_name = %s';
            if ( $this->connection->queryScalar( $query, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'SELECT request_id FROM {register_requests} WHERE user_login = %s';
            if ( $this->connection->queryScalar( $query, $login ) !== false )
                throw new System_Api_Error( System_Api_Error::LoginAlreadyExists );

            $query = 'SELECT user_id FROM {users} WHERE UPPER( user_email ) = %s';
            if ( $this->connection->queryScalar( $query, mb_strtoupper( $email ) ) !== false )
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
        $eventLog->addEvent( System_Api_EventLog::Access, System_Api_EventLog::Information, $eventLog->t( 'log.UserRegistered', array( $name ) ) );

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
            $eventLog->t( 'log.RegistrationActivated', array( $request[ 'user_name' ] ) ) );

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
            $query = 'SELECT user_id FROM {users} WHERE user_name = %s';
            if ( $this->connection->queryScalar( $query, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'SELECT user_id FROM {users} WHERE user_login = %s';
            if ( $this->connection->queryScalar( $query, $login ) !== false )
                throw new System_Api_Error( System_Api_Error::LoginAlreadyExists );

            $query = 'INSERT INTO {users} ( user_login, user_name, user_passwd, user_access, passwd_temp, user_email ) VALUES ( %s, %s, %s, %d, %d, %s )';
            $this->connection->execute( $query, $login, $name, $hash, System_Const::NormalAccess, 0, $email );

            $userId = $this->connection->getInsertId( 'users', 'user_id' );

            $query = 'DELETE FROM {register_requests} WHERE request_id = %d';
            $this->connection->execute( $query, $requestId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.RegistrationApproved', array( $name ) ) );

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
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.RegistrationRejected', array( $name ) ) );
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
