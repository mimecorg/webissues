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
* Manage projects and folders.
*
* Like all API classes, this class does not check permissions to perform
* an operation and does not validate the input values. An error is thrown
* only if the requested object does not exist or is inaccessible.
*/
class System_Api_ProjectManager extends System_Api_Base
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Administrator access is required for the project or folder. */
    const RequireAdministrator = 1;
    /** Force deletion with entire contents. */
    const ForceDelete = 2;
    /*@}*/

    private static $folders = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get list of accessible projects.
    * @param $flags If RequireAdministrator is passed only projects
    * to which the user has administrator access are returned.
    * @return An array of associative arrays representing project.
    */
    public function getProjects( $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( !$principal->isAuthenticated() ) {
            if ( $flags & self::RequireAdministrator )
                throw new System_Api_Error( System_Api_Error::AccessDenied );
            $query = 'SELECT p.project_id, p.project_name, p.stamp_id, p.is_public, %3d AS project_access FROM {projects} AS p'
                . ' WHERE p.is_archived = 0 AND p.is_public = 1';
        } else if ( !$principal->isAdministrator() ) {
            $query = 'SELECT p.project_id, p.project_name, p.stamp_id, p.is_public, r.project_access FROM {projects} AS p'
                . ' JOIN {effective_rights} AS r ON r.project_id = p.project_id AND r.user_id = %1d';
            if ( $flags & self::RequireAdministrator )
                $query .= ' AND r.project_access = %2d';
            $query .= ' WHERE p.is_archived = 0';
        } else {
            $query = 'SELECT p.project_id, p.project_name, p.stamp_id, p.is_public, %2d AS project_access FROM {projects} AS p'
                . ' WHERE p.is_archived = 0';
        }
        $query .= ' ORDER BY p.project_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::NormalAccess );
    }

    /**
    * Get the project with given identifier.
    * @param $projectId Identifier of the project.
    * @param $flags If RequireAdministrator is passed an error is thrown
    * if the user does not have administrator access to the project.
    * @return Array containing project details.
    */
    public function getProject( $projectId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT p.project_id, p.project_name, p.stamp_id, p.descr_id, p.descr_stub_id, p.is_public,';
        if ( !$principal->isAuthenticated() )
            $query .= ' %4d AS project_access';
        else if ( !$principal->isAdministrator() )
            $query .= ' r.project_access';
        else
            $query .= ' %3d AS project_access';
        $query .= ' FROM {projects} AS p';
        if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = p.project_id AND r.user_id = %2d';
        $query .= ' WHERE p.project_id = %1d AND p.is_archived = 0';
        if ( !$principal->isAuthenticated() )
            $query .= ' AND p.is_public = 1';

        if ( !( $project = $this->connection->queryRow( $query, $projectId, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::NormalAccess ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownProject );

        if ( $flags & self::RequireAdministrator && $project[ 'project_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $project;
    }

    /**
    * Get list of folders in all accessible projects.
    * @param $flags If RequireAdministrator is passed only folders from
    * projects to which the user has administrator access are returned.
    * @return An array of associative arrays representing folders.
    */
    public function getFolders( $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT f.folder_id, f.project_id, f.folder_name, f.type_id, f.stamp_id, t.type_name FROM {folders} AS f';
        if ( !$principal->isAdministrator() ) {
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %1d';
            if ( $flags & self::RequireAdministrator )
                $query .= ' AND r.project_access = %2d';
        }
        $query .= ' JOIN {projects} AS p ON p.project_id = f.project_id'
            . ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
            . ' WHERE p.is_archived = 0'
            . ' ORDER BY f.folder_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess );
    }

    /**
    * Get the project with given identifier. Information about the related
    * project is also returned. Folders are cached to prevent accessing
    * the database unnecessarily.
    * @param $folderId Identifier of the folder.
    * @param $flags If RequireAdministrator is passed an error is thrown
    * if the user does not have administrator access to the project containing
    * the folder.
    * @return Array containing project details.
    */
    public function getFolder( $folderId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( isset( self::$folders[ $folderId ] ) ) {
            $folder = self::$folders[ $folderId ];
        } else {
            $query = 'SELECT f.folder_id, f.folder_name, f.type_id, f.stamp_id, p.project_id, p.project_name, t.type_name,';
            if ( !$principal->isAuthenticated() )
                $query .= ' %4d AS project_access';
            else if ( !$principal->isAdministrator() )
                $query .= ' r.project_access';
            else
                $query .= ' %3d AS project_access';
            $query .= ' FROM {folders} AS f'
                . ' JOIN {projects} AS p ON p.project_id = f.project_id'
                . ' JOIN {issue_types} AS t ON t.type_id = f.type_id';
            if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
                $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
            $query .= ' WHERE f.folder_id = %1d AND p.is_archived = 0';
            if ( !$principal->isAuthenticated() )
                $query .= ' AND p.is_public = 1';

            if ( !( $folder = $this->connection->queryRow( $query, $folderId, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::NormalAccess ) ) )
                throw new System_Api_Error( System_Api_Error::UnknownFolder );

            self::$folders[ $folderId ] = $folder;
        }

        if ( $flags & self::RequireAdministrator && $folder[ 'project_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $folder;
    }

    /**
    * Get list of folders of the specified type.
    * @param $type The issue type of the folders.
    * @param $flags If RequireAdministrator is passed only folders from
    * projects to which the user has administrator access are returned.
    * @return An array of associative arrays representing folders.
    */
    public function getFoldersByIssueType( $type, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        $query = 'SELECT f.folder_id, f.project_id, f.folder_name, f.type_id, f.stamp_id, t.type_name FROM {folders} AS f';
        if ( !$principal->isAdministrator() ) {
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %1d';
            if ( $flags & self::RequireAdministrator )
                $query .= ' AND r.project_access = %2d';
        }
        $query .= ' JOIN {projects} AS p ON p.project_id = f.project_id'
            . ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
            . ' WHERE t.type_id = %3d AND p.is_archived = 0'
            . ' ORDER BY f.folder_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, $typeId );
    }

    public function getFolderFromIssue( $issue )
    {
        $folder = array();
        $folder[ 'folder_id' ] = $issue[ 'folder_id' ];
        $folder[ 'folder_name' ] = $issue[ 'folder_name' ];
        $folder[ 'type_id' ] = $issue[ 'type_id' ];
        $folder[ 'type_name' ] = $issue[ 'type_name' ];
        $folder[ 'project_id' ] = $issue[ 'project_id' ];
        $folder[ 'project_name' ] = $issue[ 'project_name' ];
        $folder[ 'project_access' ] = $issue[ 'project_access' ];
        return $folder;
    }

    /**
    * Get the project description.
    * @param $project The project for which the description is retrieved.
    * @return An associative array representing the description.
    */
    public function getProjectDescription( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT pd.descr_text, pd.descr_format, p.project_id,'
            . ' s.user_id AS modified_user, s.stamp_time AS modified_date, u.user_name AS modified_by'
            . ' FROM {project_descriptions} AS pd'
            . ' JOIN {projects} AS p ON p.project_id = pd.project_id'
            . ' JOIN {stamps} AS s ON s.stamp_id = p.descr_id'
            . ' JOIN {users} AS u ON u.user_id = s.user_id'
            . ' WHERE pd.project_id = %d';

        if ( !( $descr = $this->connection->queryRow( $query, $projectId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownDescription );

        return $descr;
    }

    /**
    * Return @c true if the description has been added or modified since the given stamp.
    */
    public function isDescriptionModified( $project, $sinceStamp )
    {
        return $project[ 'descr_id' ] != null && $project[ 'descr_id' ] > $sinceStamp;
    }

    /**
    * Return @c true if the description has been deleted since the given stamp.
    */
    public function isDescriptionDeleted( $project, $sinceStamp )
    {
        return $project[ 'descr_id' ] == null && $sinceStamp > 0 && $project[ 'descr_stub_id' ] > $sinceStamp;
    }

    /**
    * Return an array of all project and folder names.
    */
    public function getFoldersMap()
    {
        $query = 'SELECT f.folder_id, f.folder_name, p.project_name'
            . ' FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id'
            . ' WHERE p.is_archived = 0';

        return $this->connection->queryTable( $query );
    }

    /**
    * Create a new project. An error is thrown if a project with given name
    * already exists.
    * @param $name The name of the project to create.
    * @return The identifier of the new project.
    */
    public function addProject( $name )
    {
        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'projects' );

        try {
            $query = 'SELECT project_id FROM {projects} WHERE project_name = %s';
            if ( $this->connection->queryScalar( $query, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::ProjectAlreadyExists );

            $query = 'INSERT INTO {projects} ( project_name ) VALUES ( %s )';
            $this->connection->execute( $query, $name );

            $projectId = $this->connection->getInsertId( 'projects', 'project_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Added project "%1"', null, $name ) );

        return $projectId;
    }

    /**
    * Rename a project. An error is thrown if another project with given name
    * already exists.
    * @param $project The project to rename.
    * @param $newName The new name of the project.
    * @return @c true if the name was modified.
    */
    public function renameProject( $project, $newName )
    {
        $projectId = $project[ 'project_id' ];
        $oldName = $project[ 'project_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'projects' );

        try {
            $query = 'SELECT project_id FROM {projects} WHERE project_name = %s';
            if ( $this->connection->queryScalar( $query, $newName ) !== false )
                throw new System_Api_Error( System_Api_Error::ProjectAlreadyExists );

            $query = 'UPDATE {projects} SET project_name = %s WHERE project_id = %d';
            $this->connection->execute( $query, $newName, $projectId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Renamed project "%1" to "%2"', null, $oldName, $newName ) );

        return true;
    }

    /**
    * Set global access for the project.
    * @param $project The project to change access for.
    * @param $isPublic Flag indicating whether public access is allowed.
    * @return @c true if the global access was modified.
    */
    public function setProjectAccess( $project, $isPublic )
    {
        $projectId = $project[ 'project_id' ];
        $wasPublic = $project[ 'is_public' ];

        if ( $isPublic == $wasPublic )
            return false;

        $query = 'UPDATE {projects} SET is_public = %d WHERE project_id = %d';
        $this->connection->execute( $query, $isPublic, $projectId );

        $eventLog = new System_Api_EventLog( $this );
        if ( $isPublic ) {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Enabled public access for project "%1"', null, $project[ 'project_name' ] ) );
        } else {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Disabled public access for project "%1"', null, $project[ 'project_name' ] ) );
        }

        return true;
    }

    /**
    * Archive a project.
    * @param $project The project to archive.
    * @return @c true if the project was archived.
    */
    public function archiveProject( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'UPDATE {projects} SET is_archived = 1 WHERE project_id = %d';
        $this->connection->execute( $query, $projectId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Archived project "%1"', null, $project[ 'project_name' ] ) );

        return true;
    }

    /**
    * Restore a project.
    * @param $project The project to archive.
    * @return @c true if the project was restored.
    */
    public function restoreProject( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'UPDATE {projects} SET is_archived = 0 WHERE project_id = %d';
        $this->connection->execute( $query, $projectId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Restored project "%1"', null, $project[ 'project_name' ] ) );

        return true;
    }

    /**
    * Delete a project.
    * @param $project The project to delete.
    * @param $flags If ForceDelete is passed the project is deleted
    * even if it contains folders. Otherwise an error is thrown.
    * @return @c true if the project was deleted.
    */
    public function deleteProject( $project, $flags = 0 )
    {
        $projectId = $project[ 'project_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'folders' );

        try {
            if ( !( $flags & self::ForceDelete ) && $this->checkProjectNotEmpty( $project ) )
                throw new System_Api_Error( System_Api_Error::CannotDeleteProject );

            $query = 'SELECT fl.file_id FROM {files} AS fl'
                . ' JOIN {changes} ch ON ch.change_id = fl.file_id'
                . ' JOIN {issues} i ON i.issue_id = ch.issue_id'
                . ' JOIN {folders} f ON f.folder_id = i.folder_id'
                . ' WHERE f.project_id = %d AND fl.file_storage = %d';
            $files = $this->connection->queryTable( $query, $projectId, System_Api_IssueManager::FileSystemStorage );

            $query = 'DELETE FROM {projects} WHERE project_id = %d';
            $this->connection->execute( $query, $projectId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        if ( $flags & self::ForceDelete ) {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Warning,
                $eventLog->tr( 'Deleted project "%1" with folders', null, $project[ 'project_name' ] ) );
        } else {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Deleted project "%1"', null, $project[ 'project_name' ] ) );
        }

        $issueManager = new System_Api_IssueManager();
        $issueManager->deleteFiles( $files );

        return true;
    }

    /**
    * Check if the project is not empty.
    * @return @c true if the project contains folders.
    */
    public function checkProjectNotEmpty( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT COUNT(*) FROM {folders} WHERE project_id = %d';

        return $this->connection->queryScalar( $query, $projectId ) > 0;
    }

    /**
    * Create a new folder in the given project. An error is thrown if a folder
    * with given name already exists in the project.
    * @param $project The project where the new folder is located.
    * @param $type The type of issues stored in the new folder.
    * @param $name The name of the folder to create.
    * @return The identifier of the new folder.
    */
    public function addFolder( $project, $type, $name )
    {
        $projectId = $project[ 'project_id' ];
        $typeId = $type[ 'type_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'folders' );

        try {
            $query = 'SELECT folder_id FROM {folders} WHERE project_id = %d AND folder_name = %s';
            if ( $this->connection->queryScalar( $query, $projectId, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::FolderAlreadyExists );

            $query = 'INSERT INTO {folders} ( project_id, type_id, folder_name ) VALUES ( %d, %d, %s )';
            $this->connection->execute( $query, $projectId, $typeId, $name );

            $folderId = $this->connection->getInsertId( 'folders', 'folder_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Added folder "%1" to project "%2"', null, $name, $project[ 'project_name' ] ) );

        return $folderId;
    }

    /**
    * Rename a folder. An error is thrown if another folder with given name
    * already exists in the project.
    * @param $folder The folder to rename.
    * @param $newName The new name of the folder.
    * @return @c true if the name was modified.
    */
    public function renameFolder( $folder, $newName )
    {
        $folderId = $folder[ 'folder_id' ];
        $projectId = $folder[ 'project_id' ];
        $oldName = $folder[ 'folder_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'folders' );

        try {
            $query = 'SELECT folder_id FROM {folders} WHERE project_id = %d AND folder_name = %s';
            if ( $this->connection->queryScalar( $query, $projectId, $newName ) !== false )
                throw new System_Api_Error( System_Api_Error::FolderAlreadyExists );

            $query = 'UPDATE {folders} SET folder_name = %s WHERE folder_id = %d';
            $this->connection->execute( $query, $newName, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Renamed folder "%1" to "%2" in project "%3"', null, $oldName, $newName, $folder[ 'project_name' ] ) );

        return true;
    }

    /**
    * Delete a folder.
    * @param $folder The folder to delete.
    * @param $flags If ForceDelete is passed the folder is deleted
    * even if it contains issues. Otherwise an error is thrown.
    * @return @c true if the folder was deleted.
    */
    public function deleteFolder( $folder, $flags = 0 )
    {
        $folderId = $folder[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'issues' );

        try {
            if ( !( $flags & self::ForceDelete ) && $this->checkFolderNotEmpty( $folder ) )
                throw new System_Api_Error( System_Api_Error::CannotDeleteFolder );

            $query = 'SELECT fl.file_id FROM {files} AS fl'
                . ' JOIN {changes} ch ON ch.change_id = fl.file_id'
                . ' JOIN {issues} i ON i.issue_id = ch.issue_id'
                . ' WHERE i.folder_id = %d AND fl.file_storage = %d';
            $files = $this->connection->queryTable( $query, $folderId, System_Api_IssueManager::FileSystemStorage );

            $query = 'DELETE FROM {folders} WHERE folder_id = %d';
            $this->connection->execute( $query, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        if ( $flags & self::ForceDelete ) {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Warning,
                $eventLog->tr( 'Deleted folder "%1" with issues from project "%2"', null, $folder[ 'folder_name' ], $folder[ 'project_name' ] ) );
        } else {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Deleted folder "%1" from project "%2"', null, $folder[ 'folder_name' ], $folder[ 'project_name' ] ) );
        }

        $issueManager = new System_Api_IssueManager();
        $issueManager->deleteFiles( $files );

        return true;
    }

    /**
    * Check if the folder is not empty.
    * @return @c true if the folder contains issues.
    */
    public function checkFolderNotEmpty( $folder )
    {
        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT COUNT(*) FROM {issues} WHERE folder_id = %d';

        return $this->connection->queryScalar( $query, $folderId ) > 0;
    }

    /**
    * Move a folder to another project.
    * @param $folder The folder to move.
    * @param $project The target project.
    * @return @c true if the foler was moved.
    */
    public function moveFolder( $folder, $project )
    {
        $folderId = $folder[ 'folder_id' ];
        $fromProjectId = $folder[ 'project_id' ];
        $name = $folder[ 'folder_name' ];

        $toProjectId = $project[ 'project_id' ];

        if ( $fromProjectId == $toProjectId )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'folders' );

        try {
            $query = 'SELECT folder_id FROM {folders} WHERE project_id = %d AND folder_name = %s';
            if ( $this->connection->queryScalar( $query, $toProjectId, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::FolderAlreadyExists );

            $query = 'UPDATE {folders} SET project_id = %d WHERE folder_id = %d';
            $this->connection->execute( $query, $toProjectId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Moved folder "%1" from project "%2" to "%3"', null, $folder[ 'folder_name' ],
            $folder[ 'project_name' ], $project[ 'project_name' ] ) );

        return true;
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getProjectsColumns()
    {
        return array( 'name' => 'p.project_name COLLATE LOCALE' );
    }

    /**
    * Return the total number of accessible projects.
    * @param $flags If RequireAdministrator is passed only projects
    * to which the user has administrator access are returned.
    * @return The number of projects.
    */
    public function getProjectsCount( $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();
        if ( !$principal->isAuthenticated() ) {
            $query = 'SELECT COUNT(*) FROM {projects} AS p WHERE p.is_public = 1 AND p.is_archived = 0';
        } else if ( !$principal->isAdministrator() ) {
            $query = 'SELECT COUNT(*) FROM {projects} AS p'
                . ' JOIN {effective_rights} AS r ON r.project_id = p.project_id AND r.user_id = %1d';
            if ( $flags & self::RequireAdministrator )
                $query .= ' AND r.project_access = %2d';
            $query .= ' WHERE p.is_archived = 0';
        } else {
            $query = 'SELECT COUNT(*) FROM {projects} AS p WHERE p.is_archived = 0';
        }

        return $this->connection->queryScalar( $query, $principal->getUserId(), System_Const::AdministratorAccess );
    }

    /**
    * Get paged list of accessible projects.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @param $flags If RequireAdministrator is passed only projects
    * to which the user has administrator access are returned.
    * @return An array of associative arrays representing projects.
    */
    public function getProjectsPage( $orderBy, $limit, $offset, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();
        if ( !$principal->isAuthenticated() ) {
            $query = 'SELECT p.project_id, p.project_name, p.is_public, p.descr_id, %3d AS project_access FROM {projects} AS p WHERE p.is_public = 1 AND p.is_archived = 0';
        } else if ( !$principal->isAdministrator() ) {
            $query = 'SELECT p.project_id, p.project_name, p.is_public, p.descr_id, r.project_access FROM {projects} AS p'
                . ' JOIN {effective_rights} AS r ON r.project_id = p.project_id AND r.user_id = %1d';
            if ( $flags & self::RequireAdministrator )
                $query .= ' AND r.project_access = %2d';
            $query .= ' WHERE p.is_archived = 0';
        } else {
            $query = 'SELECT p.project_id, p.project_name, p.is_public, p.descr_id, %2d AS project_access FROM {projects} AS p WHERE p.is_archived = 0';
        }

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::NormalAccess );
    }

    /**
    * Return the total number of archived projects.
    * @return The number of projects.
    */
    public function getArchivedProjectsCount()
    {
        $query = 'SELECT COUNT(*) FROM {projects} AS p WHERE p.is_archived = 1';

        return $this->connection->queryScalar( $query );
    }

    /**
    * Get paged list of archived projects.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing projects.
    */
    public function getArchivedProjectsPage( $orderBy, $limit, $offset )
    {
        $query = 'SELECT p.project_id, p.project_name, p.descr_id FROM {projects} AS p WHERE p.is_archived = 1';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset );
    }

    /**
    * Get the archived project with given identifier.
    * @param $projectId Identifier of the project.
    * @return Array containing project details.
    */
    public function getArchivedProject( $projectId )
    {
        $query = 'SELECT p.project_id, p.project_name FROM {projects} AS p WHERE p.project_id = %d AND p.is_archived = 1';

        if ( !( $project = $this->connection->queryRow( $query, $projectId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownProject );

        return $project;
    }

    /**
    * Get list of folders in given project.
    * @param $projectId Identifier of the project.
    * @return An array of associative arrays representing folders.
    */
    public function getFoldersForProject( $project )
    {
        $projectId = $project[ 'project_id' ];

        $query = 'SELECT f.folder_id, f.folder_name, t.type_name FROM {folders} AS f'
            . ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
            . ' WHERE f.project_id = %d'
            . ' ORDER BY f.folder_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $projectId );
    }

    /**
    * Get list of folders in given projects.
    * @param $projects Array of projects.
    * @return An array of associative arrays representing folders.
    */
    public function getFoldersForProjects( $projects )
    {
        if ( empty( $projects ) )
            return array();

        $ids = array();
        foreach ( $projects as $project )
            $ids[] = $project[ 'project_id' ];

        $query = 'SELECT f.folder_id, f.project_id, f.folder_name, f.type_id, f.stamp_id, t.type_name FROM {folders} AS f'
            . ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
            . ' WHERE f.project_id IN ( %%d )'
            . ' ORDER BY f.folder_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $ids );
    }

    /**
    * Add a description to the project.
    * @param $project The project to modify.
    * @param $text Content of the description.
    * @param $format The format of the description.
    * @return The identifier of the change.
    */
    public function addProjectDescription( $project, $text, $format )
    {
        if ( $project[ 'descr_id' ] != null )
            throw new System_Api_Error( System_Api_Error::DescriptionAlreadyExists );

        $principal = System_Api_Principal::getCurrent();

        $projectId = $project[ 'project_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {project_descriptions} ( project_id, descr_text, descr_format ) VALUES ( %d, %s, %d )';
            $this->connection->execute( $query, $projectId, $text, $format );

            $query = 'UPDATE {projects} SET descr_id = %1d WHERE project_id = %2d AND COALESCE( descr_id, descr_stub_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $projectId );

            $query = 'UPDATE {projects} SET stamp_id = %1d WHERE project_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $projectId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Edit the existing description of the project.
    * @param $descr The description to modify.
    * @param $newText Content of the description.
    * @param $newFormat The format of the description.
    * @return The identifier of the change or @c null if no change was made.
    */
    public function editProjectDescription( $descr, $newText, $newFormat )
    {
        $principal = System_Api_Principal::getCurrent();

        $projectId = $descr[ 'project_id' ];
        $oldText = $descr[ 'descr_text' ];
        $oldFormat = $descr[ 'descr_format' ];

        if ( $newText == $oldText && $newFormat == $oldFormat )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'UPDATE {project_descriptions} SET descr_text = %s, descr_format = %d  WHERE project_id = %d';
            $this->connection->execute( $query, $newText, $newFormat, $projectId );

            $query = 'UPDATE {projects} SET descr_id = %1d WHERE project_id = %2d AND COALESCE( descr_id, descr_stub_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $projectId );

            $query = 'UPDATE {projects} SET stamp_id = %1d WHERE project_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $projectId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Delete the description of the project.
    * @param $descr The description to delete.
    * @return The identifier of the change.
    */
    public function deleteProjectDescription( $descr )
    {
        $principal = System_Api_Principal::getCurrent();

        $projectId = $descr[ 'project_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'DELETE FROM {project_descriptions} WHERE project_id = %d';
            $this->connection->execute( $query, $projectId );

            $query = 'UPDATE {projects} SET descr_id = NULL, descr_stub_id = %1d WHERE project_id = %2d AND COALESCE( descr_id, descr_stub_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $projectId );

            $query = 'UPDATE {projects} SET stamp_id = %1d WHERE project_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $projectId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }
}
