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
* Manage users and project members.
*/
class System_Api_UserManager extends System_Api_Base
{
    /**
    * @name User Types
    */
    /*@{*/
    /** Active users. */
    const Active = 1;
    /** Disabled users. */
    const Disabled = 2;
    /*@}*/

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get list of users.
    * @return An array of associative arrays representing users.
    */
    public function getUsers()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT user_id, user_name'
            . ' FROM {users}';
        if ( !$principal->isAuthenticated() ) {
            $query .= ' WHERE user_id IN ( SELECT r.user_id FROM {rights} AS r'
                . ' INNER JOIN {projects} AS p ON p.project_id = r.project_id'
                . ' WHERE p.is_archived = 0 AND p.is_public = 1 )'
                . ' OR user_access = %2d';
        } else if ( !$principal->isAdministrator() ) {
            $query .= ' WHERE EXISTS( SELECT * FROM {rights}'
                . ' WHERE user_id = %1d AND project_access = %2d AND project_id IN ( SELECT project_id FROM {projects} WHERE is_archived = 0 ) )'
                . ' OR user_id IN ( SELECT r.user_id FROM {rights} AS r'
                . ' INNER JOIN {projects} AS p ON p.project_id = r.project_id'
                . ' WHERE p.is_archived = 0 AND ( p.project_id IN ( SELECT project_id FROM {rights} WHERE user_id = %1d ) OR p.is_public = 1 ) )'
                . ' OR user_access = %2d';
        }
        $query .= ' ORDER BY user_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess );
    }

    /**
    * Get list of users with all details.
    * @return An array of associative arrays representing users.
    */
    public function getUsersWithDetails()
    {
        $query = 'SELECT u.user_id, u.user_login, u.user_name, u.user_access, u.user_email,'
            . ' ( CASE WHEN EXISTS( SELECT * FROM {rights} AS r'
            . ' WHERE r.user_id = u.user_id AND r.project_access = %1d'
            . ' AND r.project_id IN ( SELECT project_id FROM {projects} WHERE is_archived = 0 ) ) THEN 1 ELSE 0 END ) AS project_admin'
            . ' FROM {users} AS u'
            . ' ORDER BY u.user_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, System_Const::AdministratorAccess );
    }

    /**
    * Get the user with given identifier.
    * @param $userId Identifier of the user.
    * @return Array containing the user.
    */
    public function getUser( $userId )
    {
        $query = 'SELECT user_id, user_login, user_name, user_access, user_email, user_language FROM {users} WHERE user_id = %d';

        if ( !( $user = $this->connection->queryRow( $query, $userId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownUser );

        return $user;
    }

    /**
    * Get the rights of all project members.
    * @return An array of associative arrays representing member rights.
    */
    public function getRights()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT r.project_id, r.user_id, r.project_access FROM {rights} AS r';
        $query .= ' JOIN {projects} AS p ON p.project_id = r.project_id'
            . ' WHERE p.is_archived = 0';
        if ( !$principal->isAuthenticated() )
            $query .= ' AND p.is_public = 1';
        else if ( !$principal->isAdministrator() )
            $query .= ' AND ( p.project_id IN ( SELECT project_id FROM {rights} WHERE user_id = %1d ) OR p.is_public = 1 )';

        return $this->connection->queryTable( $query, $principal->getUserId() );
    }

    /**
    * Get list of the members of given project.
    * @param $project The project to retrieve members.
    * @return An array of associative arrays representing members.
    */
    public function getMembers( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT project_id, user_id, project_access FROM {rights} WHERE project_id = %d';

        return $this->connection->queryTable( $query, $projectId );
    }

    /**
    * Check the access to a project for the given user.
    * @param $user The user whose access is modified.
    * @param $project The project to which the access is related.
    * @return An associative array representing member.
    */
    public function getMember( $user, $project )
    {
        $userId = $user[ 'user_id' ];
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT project_id, user_id, project_access FROM {rights} WHERE user_id = %d AND project_id = %d';

        if ( !( $member = $this->connection->queryRow( $query, $userId, $projectId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownUser );

        return $member;
    }

    /**
    * Get list of the projects of given user.
    * @param $user The user to retrieve member projects.
    * @return An array of associative arrays representing projects.
    */
    public function getUserProjects( $user )
    {
        $userId = $user[ 'user_id' ];

        $query = 'SELECT r.project_id, r.user_id, r.project_access FROM {rights} AS r'
            . ' JOIN {projects} AS p ON p.project_id = r.project_id'
            . ' WHERE r.user_id = %d AND p.is_archived = 0';

        return $this->connection->queryTable( $query, $userId );
    }

    /**
    * Check if the value is a valid user name. This is a helper method for
    * System_Api_Validator.
    * @param $value The value to validate.
    * @param $projectId Identifier of the project the user must be a member or
    * or @c null to accept all users.
    */
    public function checkUserName( $value, $projectId )
    {
        $query = 'SELECT u.user_id FROM {users} AS u';
        if ( $projectId )
            $query .= ' JOIN {rights} AS r ON r.user_id = u.user_id AND r.project_id = %2d';
        $query .= ' WHERE u.user_name = %1s';

        if ( $this->connection->queryScalar( $query, $value, $projectId ) === false )
            throw new System_Api_Error( System_Api_Error::NoMatchingItem );
    }

    /**
    * Return only users which have valid email and are not disabled.
    */
    public function getUsersWithEmail()
    {
        $query = 'SELECT user_id, user_name, user_access, user_email, user_language'
            . ' FROM {users}'
            . ' WHERE user_access > %d AND user_email IS NOT NULL';

        return $this->connection->queryTable( $query, System_Const::NoAccess );
    }

    /**
    * Get the user with specified email.
    * @param $email Email of the user.
    * @return Array containing the user.
    */
    public function getUserByEmail( $email )
    {
        $query = 'SELECT user_id, user_login, user_name, user_access, user_email, user_language'
            . ' FROM {users}'
            . ' WHERE UPPER( user_email ) = %s AND user_access > %d';

        if ( !( $user = $this->connection->queryRow( $query, mb_strtoupper( $email ), System_Const::NoAccess ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownUser );

        return $user;
    }

    /**
    * Create a new user. An error is thrown if a user with given login or name
    * already exists. The user has System_Const::NormalAccess by default.
    * @param $login The login of the user.
    * @param $name The name of the user.
    * @param $password The password of the user.
    * @param $isTemp If @c true the password is temporary and user must
    * change it at next logon.
    * @param $invitationKey The key for reset password link.
    * @param $email The email of the user.
    * @param $language The language of the user.
    * @return Identifier of the user.
    */
    public function addUser( $login, $name, $password, $isTemp, $invitationKey, $email, $language )
    {
        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'users' );

        try {
            $query = 'SELECT user_id FROM {users} WHERE user_name = %s';
            if ( $this->connection->queryScalar( $query, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'SELECT user_id FROM {users} WHERE user_login = %s';
            if ( $this->connection->queryScalar( $query, $login ) !== false )
                throw new System_Api_Error( System_Api_Error::LoginAlreadyExists );

            if ( $email != '' ) {
                $query = 'SELECT user_id FROM {users} WHERE UPPER( user_email ) = %s';
                if ( $this->connection->queryScalar( $query, mb_strtoupper( $email ) ) !== false )
                    throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );
            }

            if ( $invitationKey == null ) {
                $passwordHash = new System_Core_PasswordHash();
                $hash = $passwordHash->hashPassword( $password );

                $query = 'INSERT INTO {users} ( user_login, user_name, user_passwd, user_access, passwd_temp, user_email, user_language )'
                    . ' VALUES ( %s, %s, %s, %d, %d, %s?, %s? )';
                $this->connection->execute( $query, $login, $name, $hash, System_Const::NormalAccess, $isTemp, $email, $language );
            } else {
                $query = 'INSERT INTO {users} ( user_login, user_name, user_access, passwd_temp, reset_key, reset_time, user_email, user_language )'
                    . ' VALUES ( %s, %s, %d, %d, %s, %d, %s?, %s? )';
                $this->connection->execute( $query, $login, $name, System_Const::NormalAccess, 0, $invitationKey, time(), $email, $language );
            }

            $userId = $this->connection->getInsertId( 'users', 'user_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserAdded', array( $name ) ) );

        return $userId;
    }

    /**
    * Set the password of a user.
    * @param $user The user whoose password is changed.
    * @param $newPassword The new password.
    * @param $isTemp If @c true the password is temporary and user must
    * change it at next logon.
    * @return @c true if the password was changed.
    */
    public function setPassword( $user, $newPassword, $isTemp )
    {
        $principal = System_Api_Principal::getCurrent();

        $userId = $user[ 'user_id' ];

        if ( $userId == $principal->getUserId() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $passwordHash = new System_Core_PasswordHash();
        $newHash = $passwordHash->hashPassword( $newPassword );

        $query = 'UPDATE {users} SET user_passwd = %s, passwd_temp = %d WHERE user_id = %d';
        $this->connection->execute( $query, $newHash, $isTemp, $userId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserPasswordChanged', array( $user[ 'user_name' ] ) ) );

        return true;
    }

    /**
    * Change the password of the current user.
    * @param $password The current password.
    * @param $newPassword The current password.
    * @return @c true if the password was changed.
    */
    public function changePassword( $password, $newPassword )
    {
        $principal = System_Api_Principal::getCurrent();

        $principal->checkNoDemoUser();

        $userId = $principal->getUserId();

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'users' );

        try {
            $query = 'SELECT user_passwd FROM {users} WHERE user_id = %d';
            $hash = $this->connection->queryScalar( $query, $userId );

            $passwordHash = new System_Core_PasswordHash();

            if ( !$passwordHash->checkPassword( $password, $hash ) )
                throw new System_Api_Error( System_Api_Error::IncorrectLogin );

            if ( $newPassword == $password )
                throw new System_Api_Error( System_Api_Error::CannotReusePassword );

            $newHash = $passwordHash->hashPassword( $newPassword );

            $query = 'UPDATE {users} SET user_passwd = %s, passwd_temp = 0 WHERE user_id = %d';
            $this->connection->execute( $query, $newHash, $userId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $name = $principal->getUserName();

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserOwnPasswordChanged', array( $name ) ) );

        return true;
    }

    public function getUserWithResetKey( $key )
    {
        $query = 'SELECT user_id, user_login, user_name, user_access, user_email, user_language, reset_time FROM {users} WHERE reset_key = %s';

        if ( !( $user = $this->connection->queryRow( $query, $key ) ) )
            throw new System_Api_Error( System_Api_Error::InvalidResetKey );

        if ( $user[ 'user_access' ] == System_Const::NoAccess )
            throw new System_Api_Error( System_Api_Error::InvalidResetKey );

        $serverManager = new System_Api_ServerManager();
        $lifetime = $serverManager->getSetting( 'register_max_lifetime' );

        if ( $user[ 'reset_time' ] < time() - $lifetime )
            throw new System_Api_Error( System_Api_Error::InvalidResetKey );

        return $user;
    }

    /**
    * Set the password reset key for given user.
    * @param $user The user to set the key for.
    * @param $key The password reset key.
    */
    public function setPasswordResetKey( $user, $key )
    {
        $userId = $user[ 'user_id' ];

        $query = 'UPDATE {users} SET reset_key = %s, reset_time = %d WHERE user_id = %d';
        $this->connection->execute( $query, $key, time(), $userId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserPasswordResetSent', array( $user[ 'user_name' ] ) ) );
    }

    /**
    * Reset the password of a user.
    * @param $user The user whoose password is changed.
    * @param $newPassword The new password.
    */
    public function resetPassword( $user, $newPassword )
    {
        $userId = $user[ 'user_id' ];

        $passwordHash = new System_Core_PasswordHash();
        $newHash = $passwordHash->hashPassword( $newPassword );

        $query = 'UPDATE {users} SET user_passwd = %s, passwd_temp = 0, reset_key = NULL, reset_time = NULL WHERE user_id = %d';
        $this->connection->execute( $query, $newHash, $userId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserPasswordReset', array( $user[ 'user_name' ] ) ) );
    }

    /**
    * Change the properties of a user.
    * @param $user The user to modify.
    * @param $newName The new name of the user.
    * @param $newLogin The new login of the user.
    * @param $newEmail The new email of the user.
    * @param $newLanguage The new language of the user.
    * @return @c true if the user was modified.
    */
    public function editUser( $user, $newName, $newLogin, $newEmail, $newLanguage )
    {
        $userId = $user[ 'user_id' ];
        $oldName = $user[ 'user_name' ];
        $oldLogin = $user[ 'user_login' ];
        $oldEmail = $user[ 'user_email' ];
        $oldLanguage = $user[ 'user_language' ];

        if ( $newName == $oldName && $newLogin == $oldLogin && $newEmail == $oldEmail && $newLanguage == $oldLanguage )
            return false;

        System_Api_Principal::getCurrent()->checkNoDemoUser();

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'users' );

        try {
            if ( $newName != $oldName ) {
                $query = 'SELECT user_id FROM {users} WHERE user_name = %s';
                if ( $this->connection->queryScalar( $query, $newName ) !== false )
                    throw new System_Api_Error( System_Api_Error::UserAlreadyExists );
            }

            if ( $newLogin != $oldLogin ) {
                $query = 'SELECT user_id FROM {users} WHERE user_login = %s';
                if ( $this->connection->queryScalar( $query, $newLogin ) !== false )
                    throw new System_Api_Error( System_Api_Error::LoginAlreadyExists );
            }

            if ( $newEmail != '' && mb_strtoupper( $newEmail ) != mb_strtoupper( $oldEmail ) ) {
                $query = 'SELECT user_id FROM {users} WHERE UPPER( user_email ) = %s';
                if ( $this->connection->queryScalar( $query, mb_strtoupper( $newEmail ) ) !== false )
                    throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );
            }

            $query = 'UPDATE {users} SET user_name = %s, user_login = %s, user_email = %s!, user_language = %s! WHERE user_id = %d';
            $this->connection->execute( $query, $newName, $newLogin, $newEmail, $newLanguage, $userId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        if ( $newName != $oldName || $newLogin != $oldLogin ) {
            $eventLog = new System_Api_EventLog( $this );
            if ( $newName != $oldName )
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserRenamed', array( $oldName, $newName ) ) );
            if ( $newLogin != $oldLogin )
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserLoginChanged', array( $oldLogin, $newLogin ) ) );
        }

        return true;
    }

    /**
    * Modify the access to the server for the given user.
    * The access level of the built-in 'admin' user cannot be changed.
    * @param $user The user whoose access is modified.
    * @param $newAccess The new access level of the user.
    * @return @c true if the access level was modified.
    */
    public function grantUser( $user, $newAccess )
    {
        $principal = System_Api_Principal::getCurrent();

        $userId = $user[ 'user_id' ];
        $oldAccess = $user[ 'user_access' ];

        if ( $userId == $principal->getUserId() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        if ( $newAccess == $oldAccess )
            return false;

        $query = 'UPDATE {users} SET user_access = %d WHERE user_id = %d';
        $this->connection->execute( $query, $newAccess, $userId );

        $eventLog = new System_Api_EventLog( $this );
        switch ( $newAccess ) {
            case System_Const::NoAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserAccessDisabled', array( $user[ 'user_name' ] ) ) );
                break;
            case System_Const::NormalAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserAccessRegular', array( $user[ 'user_name' ] ) ) );
                break;
            case System_Const::AdministratorAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.UserAccessAdministrator', array( $user[ 'user_name' ] ) ) );
                break;
        }

        return true;
    }

    /**
    * Modify the access to a project for the given user.
    * @param $user The user whose access is modified.
    * @param $project The project to which the access is related.
    * @param $newAccess The new access level of the user.
    * @return @c true if the access level was modified.
    */
    public function grantMember( $user, $project, $newAccess )
    {
        $principal = System_Api_Principal::getCurrent();

        $projectId = $project[ 'project_id' ];
        $userId = $user[ 'user_id' ];

        if ( $userId == $principal->getUserId() && $principal->getUserAccess() != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'rights' );

        try {
            $query = 'SELECT project_access FROM {rights} WHERE project_id = %d AND user_id = %d';
            $oldAccess = $this->connection->queryScalar( $query, $projectId, $userId );
            if ( $oldAccess === false )
                $oldAccess = System_Const::NoAccess;

            if ( $newAccess == $oldAccess ) {
                $transaction->commit();
                return false;
            }

            if ( $oldAccess == System_Const::NoAccess )
                $query = 'INSERT INTO {rights} ( project_id, user_id, project_access ) VALUES ( %1d, %2d, %3d )';
            else if ( $newAccess == System_Const::NoAccess )
                $query = 'DELETE FROM {rights} WHERE project_id = %1d AND user_id = %2d';
            else
                $query = 'UPDATE {rights} SET project_access = %3d WHERE project_id = %1d AND user_id = %2d';
            $this->connection->execute( $query, $projectId, $userId, $newAccess );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        switch ( $newAccess ) {
            case System_Const::NoAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->t( 'log.MemberRemoved', array( $user[ 'user_name' ], $project[ 'project_name' ] ) ) );
                break;
            case System_Const::NormalAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->t( 'log.MemberRegular', array( $user[ 'user_name' ], $project[ 'project_name' ] ) ) );
                break;
            case System_Const::AdministratorAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->t( 'log.MemberAdministrator', array( $user[ 'user_name' ], $project[ 'project_name' ] ) ) );
                break;
        }

        return true;
    }
}
