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
        $query = 'SELECT user_id, user_login, user_name, user_access FROM {users} ORDER BY user_name COLLATE LOCALE';

        return $this->connection->queryTable( $query );
    }

    /**
    * Get the user with given identifier.
    * @param $userId Identifier of the user.
    * @return Array containing the user.
    */
    public function getUser( $userId )
    {
        $query = 'SELECT user_id, user_login, user_name, user_access FROM {users} WHERE user_id = %d';

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
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r2 ON r2.project_id = r.project_id AND r2.user_id = %d';
        $query .= ' JOIN {projects} AS p ON p.project_id = r.project_id'
            . ' WHERE p.is_archived = 0';

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
    * Get the total number of users.
    */
    public function getUsersCount( $type )
    {
        $query = 'SELECT COUNT(*) FROM {users}';
        if ( $type == self::Active )
            $query .= ' WHERE user_access <> %d';
        else if ( $type == self::Disabled )
            $query .= ' WHERE user_access = %d';

        return $this->connection->queryScalar( $query, System_Const::NoAccess );
    }

    /**
    * Get a paged list of users.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing types.
    */
    public function getUsersPage( $type, $orderBy, $limit, $offset )
    {
        $query = 'SELECT u.user_id, u.user_login, u.user_name, u.user_access, p.pref_value AS user_email'
            . ' FROM {users} AS u'
            . ' LEFT OUTER JOIN {preferences} AS p ON p.user_id = u.user_id AND p.pref_key = %s';
        if ( $type == self::Active )
            $query .= ' WHERE u.user_access <> %d';
        else if ( $type == self::Disabled )
            $query .= ' WHERE u.user_access = %d';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, 'email', System_Const::NoAccess );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getUsersColumns()
    {
        return array(
            'name' => 'u.user_name COLLATE LOCALE',
            'login' => 'u.user_login COLLATE LOCALE',
            'email' => 'p.pref_value COLLATE LOCALE',
            'access' => 'u.user_access'
            );
    }

    /**
    * Return the number of members of given project.
    * @param $project The project to count members.
    * @return The number of members.
    */
    public function getMembersCount( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT COUNT(*) FROM {rights} WHERE project_id = %d';
 
        return $this->connection->queryScalar( $query, $projectId );
    }

    /**
    * Get paged list of the members of given project.
    * @param $project The project to retrieve members.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing members.
    */
    public function getMembersPage( $project, $orderBy, $limit, $offset )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT r.project_id, r.user_id, r.project_access, u.user_name FROM {rights} AS r'
            . ' JOIN {users} AS u ON u.user_id = r.user_id AND r.project_id = %d';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $projectId );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getMembersColumns()
    {
        return array(
            'name' => 'u.user_name COLLATE LOCALE',
            'access' => 'r.project_access'
        );
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
    * Return the number of projects for the given user.
    * @param $user The user to count projects.
    * @return The number of project.
    */
    public function getUserProjectsCount( $user )
    {
        $userId = $user[ 'user_id' ];

        $query = 'SELECT COUNT(*) FROM {rights} AS r'
            . ' JOIN {projects} AS p ON p.project_id = r.project_id'
            . ' WHERE user_id = %d AND p.is_archived = 0';
 
        return $this->connection->queryScalar( $query, $userId );
    }

    /**
    * Get paged list of the projects of given user.
    * @param $user The user to retrieve projects.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing projects.
    */
    public function getUserProjectsPage( $user, $orderBy, $limit, $offset )
    {
        $userId = $user[ 'user_id' ];

        $query = 'SELECT r.project_id, r.user_id, r.project_access, p.project_name FROM {rights} AS r'
            . ' JOIN {projects} AS p ON p.project_id = r.project_id'
            . ' WHERE r.user_id = %d AND p.is_archived = 0';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $userId );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getUserProjectsColumns()
    {
        return array(
            'name' => 'p.project_name COLLATE LOCALE',
            'access' => 'r.project_access'
        );
    }

    public function getPreferences()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT user_id, pref_key, pref_value FROM {preferences}';
        if ( !$principal->isAdministrator() )
            $query .= ' WHERE user_id = %d';

        return $this->connection->queryTable( $query, $principal->getUserId() );
    }

    public function getVisibleUsers()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT u.user_id, u.user_login, u.user_name, u.user_access'
            . ' FROM {users} AS u'
            . ' WHERE u.user_id IN ('
            . ' SELECT r1.user_id FROM {rights} AS r1'
            . ' INNER JOIN {effective_rights} AS r2 ON r2.project_id = r1.project_id'
            . ' WHERE r2.user_id = %d )'
            . ' ORDER BY u.user_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId() );
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
        $query = 'SELECT u.user_id, u.user_name, u.user_access'
            . ' FROM {users} AS u'
            . ' JOIN {preferences} AS p ON p.user_id = u.user_id AND p.pref_key = %s'
            . ' WHERE u.user_access > %d';

        return $this->connection->queryTable( $query, 'email', System_Const::NoAccess );
    }

    /**
    * Get the user with specified email.
    * @param $email Email of the user.
    * @return Array containing the user.
    */
    public function getUserByEmail( $email )
    {
        $query = 'SELECT u.user_id, u.user_login, u.user_name, u.user_access'
            . ' FROM {users} AS u'
            . ' JOIN {preferences} AS p ON p.user_id = u.user_id AND p.pref_key = %s'
            . ' WHERE UPPER( p.pref_value ) = %s AND u.user_access > %d';

        if ( !( $user = $this->connection->queryRow( $query, 'email', mb_strtoupper( $email ), System_Const::NoAccess ) ) )
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
    * @return Identifier of the user.
    */
    public function addUser( $login, $name, $password, $isTemp )
    {
        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'users' );

        try {
            $query = 'SELECT user_id FROM {users} WHERE user_login = %s OR user_name = %s';
            if ( $this->connection->queryScalar( $query, $login, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $passwordHash = new System_Core_PasswordHash();
            $hash = $passwordHash->hashPassword( $password );

            $query = 'INSERT INTO {users} ( user_login, user_name, user_passwd, user_access, passwd_temp ) VALUES ( %s, %s, %s, %d, %d )';
            $this->connection->execute( $query, $login, $name, $hash, System_Const::NormalAccess, $isTemp );

            $userId = $this->connection->getInsertId( 'users', 'user_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Added user "%1"', null, $name ) );

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
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Changed password for user "%1"', null, $user[ 'user_name' ] ) );

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
        if ( System_Core_Application::getInstance()->getSite()->getConfig( 'demo_mode' ) )
            System_Api_Principal::getCurrent()->checkAdministrator();

        $userId = System_Api_Principal::getCurrent()->getUserId();

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

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'User "%1" changed own password', null, System_Api_Principal::getCurrent()->getUserName() ) );

        return true;
    }

    /**
    * Rename a user. An error is thrown if another user with given name
    * already exists.
    * @param $user The user to rename.
    * @param $newName The new name of the user.
    * @return @c true if the name was modified.
    */
    public function renameUser( $user, $newName )
    {
        $userId = $user[ 'user_id' ];
        $oldName = $user[ 'user_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'users' );

        try {
            $query = 'SELECT user_id FROM {users} WHERE user_name = %s';
            if ( $this->connection->queryScalar( $query, $newName ) !== false )
                throw new System_Api_Error( System_Api_Error::UserAlreadyExists );

            $query = 'UPDATE {users} SET user_name = %s WHERE user_id = %d';
            $this->connection->execute( $query, $newName, $userId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Renamed user "%1" to "%2"', null, $oldName, $newName ) );

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
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->tr( 'Disabled access for user "%1"', null, $user[ 'user_name' ] ) );
                break;
            case System_Const::NormalAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->tr( 'Granted regular access for user "%1"', null, $user[ 'user_name' ] ) );
                break;
            case System_Const::AdministratorAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->tr( 'Granted system administrator access for user "%1"', null, $user[ 'user_name' ] ) );
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
                    $eventLog->tr( 'Removed user "%1" from project "%2"', null, $user[ 'user_name' ], $project[ 'project_name' ] ) );
                break;
            case System_Const::NormalAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->tr( 'Granted regular access for user "%1" to project "%2"', null, $user[ 'user_name' ], $project[ 'project_name' ] ) );
                break;
            case System_Const::AdministratorAccess:
                $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                    $eventLog->tr( 'Granted project administrator access for user "%1" to project "%2"', null, $user[ 'user_name' ], $project[ 'project_name' ] ) );
                break;
        }

        return true;
    }
}
