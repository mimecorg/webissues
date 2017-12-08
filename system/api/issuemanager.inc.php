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
* Manage issues, comments and files.
*
* Parent folders and issues are passed as arrays to ensure that all
* necessary data are available and that the parent object is accessible.
*
* All issues, changes, comments and files use common incrementally
* growing stamp identifiers to maintain the chronological order and to make
* it possible to retrieve only data added or modified since the given stamp.
*
* Like all API classes, this class does not check permissions to perform
* an operation and does not validate the input values. An error is thrown
* only if the requested object does not exist or is inaccessible.
*/
class System_Api_IssueManager extends System_Api_Base
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Administrator access is required for the issue. */
    const RequireAdministrator = 1;
    /** Administrator or owner access is required for the comment or file. */
    const RequireAdministratorOrOwner = 2;
    /** Do not return attributes with empty values. */
    const HideEmptyValues = 4;
    /*@}*/

    const DatabaseStorage = 0;
    const FileSystemStorage = 1;

    private static $issues = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get list of issues added or modified since the given stamp.
    * @param $folder Folder containing the issues.
    * @param $sinceStamp Stamp used for incremental updates.
    * @return An array of associative arrays representing issues.
    */
    public function getIssues( $folder, $sinceStamp )
    {
        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT i.issue_id, i.folder_id, i.issue_name, i.stamp_id,'
            . ' sc.stamp_time AS created_date, sc.user_id AS created_user,'
            . ' sm.stamp_time AS modified_date, sm.user_id AS modified_user'
            . ' FROM {issues} AS i'
            . ' JOIN {stamps} AS sc ON sc.stamp_id = i.issue_id'
            . ' JOIN {stamps} AS sm ON sm.stamp_id = i.stamp_id'
            . ' WHERE i.folder_id = %d AND i.stamp_id > %d';

        return $this->connection->queryTable( $query, $folderId, $sinceStamp );
    }

    /**
    * Get the issue with given identifier. Information about the related
    * project, folder and type is also returned. Issues are cached to prevent
    * accessing the database unnecessarily.
    * @param $issueId Identifier of the issue.
    * @param $flags If RequireAdministrator is passed an error is thrown
    * if the user does not have administrator access to the issue.
    * @return Array containing issue details.
    */
    public function getIssue( $issueId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( isset( self::$issues[ $issueId ] ) ) {
            $issue = self::$issues[ $issueId ];
        } else {
            $query = 'SELECT i.issue_id, i.issue_name, i.stamp_id, i.stub_id, i.descr_id, i.descr_stub_id,'
                . ' f.folder_id, f.folder_name, p.project_id, p.project_name, p.is_public, t.type_id, t.type_name,'
                . ' s.state_id, s.read_id, s.subscription_id,'
                . ' sc.stamp_time AS created_date, uc.user_id AS created_user, uc.user_name AS created_by,'
                . ' sm.stamp_time AS modified_date, um.user_id AS modified_user, um.user_name AS modified_by,';
            if ( !$principal->isAuthenticated() )
                $query .= ' %4d AS project_access';
            else if ( !$principal->isAdministrator() )
                $query .= ' r.project_access';
            else
                $query .= ' %3d AS project_access';
            $query .= ' FROM {issues} AS i'
                . ' JOIN {stamps} AS sc ON sc.stamp_id = i.issue_id'
                . ' JOIN {users} AS uc ON uc.user_id = sc.user_id'
                . ' JOIN {stamps} AS sm ON sm.stamp_id = i.stamp_id'
                . ' JOIN {users} AS um ON um.user_id = sm.user_id'
                . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
                . ' JOIN {projects} AS p ON p.project_id = f.project_id'
                . ' JOIN {issue_types} AS t ON t.type_id = f.type_id'
                . ' LEFT OUTER JOIN {issue_states} AS s ON s.issue_id = i.issue_id AND s.user_id = %2d';
            if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
                $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
            $query .= ' WHERE i.issue_id = %1d';
            if ( !$principal->isAuthenticated() )
                $query .= ' AND p.is_public = 1';
            $query .= ' AND p.is_archived = 0';

            if ( !( $issue = $this->connection->queryRow( $query, $issueId, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::NormalAccess ) ) )
                throw new System_Api_Error( System_Api_Error::UnknownIssue );

            self::$issues[ $issueId ] = $issue;
        }

        if ( $flags & self::RequireAdministrator && $issue[ 'project_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        if ( $flags & self::RequireAdministratorOrOwner && $issue[ 'project_access' ] != System_Const::AdministratorAccess
             && $issue[ 'created_user' ] != $principal->getUserId() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $issue;
    }

    /**
    * Get attribute values of issues added or modified since the given stamp.
    * @param $folder Folder containing the issues.
    * @param $sinceStamp Stamp used for incremental updates.
    * @return An array of associative arrays representing values.
    */
    public function getAttributeValuesForFolder( $folder, $sinceStamp )
    {
        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT a.attr_id, a.issue_id, a.attr_value FROM {attr_values} AS a'
            . ' JOIN {issues} AS i ON i.issue_id = a.issue_id AND i.folder_id = %d AND i.stamp_id > %d';

        return $this->connection->queryTable( $query, $folderId, $sinceStamp );
    }

    /**
    * Get attributes values for the given issue.
    * @param $issue The issue to retrieve values for.
    * @return An array of associative arrays representing values.
    */
    public function getAttributeValuesForIssue( $issue )
    {
        $issueId = $issue[ 'issue_id' ];

        $query = 'SELECT attr_id, issue_id, attr_value FROM {attr_values} WHERE issue_id = %d';

        return $this->connection->queryTable( $query, $issueId );
    }

    /**
    * Get all attributes with values for the given issue. All attributes are
    * returned including those which have empty values unless HideEmptyValues
    * is passed. Values are sorted by attribute name.
    * @param $issue The issue to retrieve values for.
    * @param $flags If HideEmptyValues is passed, empty values are not returned.
    * @return An array of associative arrays representing values.
    */
    public function getAllAttributeValuesForIssue( $issue, $flags = 0 )
    {
        $issueId = $issue[ 'issue_id' ];
        $typeId = $issue[ 'type_id' ];

        $query = 'SELECT a.attr_id, a.attr_name, a.attr_def, v.attr_value'
            . ' FROM {attr_types} AS a';
        if ( !( $flags & self::HideEmptyValues ) )
            $query .= ' LEFT OUTER';
        $query .= ' JOIN {attr_values} AS v ON v.attr_id = a.attr_id AND v.issue_id = %d'
            . ' WHERE a.type_id = %d'
            . ' ORDER BY a.attr_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $issueId, $typeId );
    }

    /**
    * Get "stubs" representing all issues moved or deleted from given folder.
    * @param $folder Folder containing the removed issues.
    * @param $sinceStamp Stamp used for incremental updates.
    * @return An array of associative arrays representing stubs.
    */
    public function getIssueStubs( $folder, $sinceStamp )
    {
        if ( $sinceStamp == 0 )
            return array();

        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT s.issue_id, i.folder_id, i.stamp_id'
            . ' FROM {issue_stubs} AS s'
            . ' LEFT OUTER JOIN {issues} AS i ON i.issue_id = s.issue_id'
            . ' WHERE s.folder_id = %d AND s.stub_id > %d AND s.prev_id <= %d AND COALESCE( i.folder_id, 0 ) <> s.folder_id';

        return $this->connection->queryTable( $query, $folderId, $sinceStamp, $sinceStamp );
    }

    /**
    * Get changes made to the issue since the given stamp.
    * @param $issue Issue containing the changes.
    * @param $sinceStamp Stamp used for incremental updates.
    * @return An array of associative arrays representing changes.
    */
    public function getChanges( $issue, $sinceStamp )
    {
        $issueId = $issue[ 'issue_id' ];

        $query = 'SELECT ch.change_id, ch.issue_id, ch.change_type, ch.stamp_id,'
            . ' sc.stamp_time AS created_date, sc.user_id AS created_user,'
            . ' sm.stamp_time AS modified_date, sm.user_id AS modified_user,'
            . ' ch.attr_id, ch.value_old, ch.value_new, ch.from_folder_id, ch.to_folder_id,'
            . ' c.comment_text, c.comment_format, f.file_name, f.file_size, f.file_descr'
            . ' FROM {changes} AS ch'
            . ' JOIN {stamps} AS sc ON sc.stamp_id = ch.change_id'
            . ' JOIN {stamps} AS sm ON sm.stamp_id = ch.stamp_id'
            . ' LEFT OUTER JOIN {comments} AS c ON c.comment_id = ch.change_id AND ch.change_type = %3d'
            . ' LEFT OUTER JOIN {files} AS f ON f.file_id = ch.change_id AND ch.change_type = %4d'
            . ' WHERE ch.issue_id = %1d AND ch.stamp_id > %2d';

        return $this->connection->queryTable( $query, $issueId, $sinceStamp, System_Const::CommentAdded, System_Const::FileAdded );
    }

    /**
    * Return only changes of the given type.
    * @param $changes Array of changes to filter.
    * @param $changeType The type of changes to return.
    * @return Filtered changes.
    */
    public function getChangesOfType( $changes, $changeType )
    {
        $result = array();

        foreach ( $changes as $change ) {
            if ( $change[ 'change_type' ] == $changeType )
                $result[] = $change;
        }

        return $result;
    }

    /**
    * Get "stubs" representing all changes deleted from given issue.
    * @param $issue Issue containing the removed changes.
    * @param $sinceStamp Stamp used for incremental updates.
    * @return An array of associative arrays representing stubs.
    */
    public function getChangeStubs( $issue, $sinceStamp )
    {
        if ( $sinceStamp == 0 )
            return array();

        $issueId = $issue[ 'issue_id' ];

        $query = 'SELECT s.change_id'
            . ' FROM {change_stubs} AS s'
            . ' WHERE s.issue_id = %d AND s.stub_id > %d AND s.change_id <= %d';

        return $this->connection->queryTable( $query, $issueId, $sinceStamp, $sinceStamp );
    }

    /**
    * Get the comment for editing or deleting.
    * @param $commentId Identifier of the comment.
    * @param $flags If RequireAdministratorOrOwner is passed an error is thrown
    * if the user does not have permission to modify the comment.
    * @return An associative array representing the comment.
    */
    public function getComment( $commentId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT c.comment_id, c.comment_text, c.comment_format, i.issue_id, i.folder_id, sc.user_id,';
        $query .= $principal->isAdministrator() ? ' %3d AS project_access' : ' r.project_access';
        $query .= ' FROM {comments} AS c'
            . ' JOIN {changes} AS ch ON ch.change_id = c.comment_id'
            . ' JOIN {issues} AS i ON i.issue_id = ch.issue_id'
            . ' JOIN {stamps} AS sc ON sc.stamp_id = ch.change_id'
            . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        $query .= ' WHERE c.comment_id = %1d AND p.is_archived = 0';

        if ( !( $comment = $this->connection->queryRow( $query, $commentId, $principal->getUserId(), System_Const::AdministratorAccess ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownComment );

        if ( $flags & self::RequireAdministratorOrOwner && $comment[ 'project_access' ] != System_Const::AdministratorAccess
             && $comment[ 'user_id' ] != $principal->getUserId() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $comment;
    }

    /**
    * Get the file for editing or deleting.
    * @param $fileId Identifier of the file.
    * @param $flags If RequireAdministratorOrOwner is passed an error is thrown
    * if the user does not have permission to modify the file.
    * @return An associative array representing the file.
    */
    public function getFile( $fileId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT fl.file_id, fl.file_name, fl.file_descr, fl.file_storage, i.issue_id, i.folder_id, sc.user_id, sc.stamp_time,';
        if ( !$principal->isAuthenticated() )
            $query .= ' %4d AS project_access';
        else if ( !$principal->isAdministrator() )
            $query .= ' r.project_access';
        else
            $query .= ' %3d AS project_access';
        $query .= ' FROM {files} AS fl'
            . ' JOIN {changes} AS ch ON ch.change_id = fl.file_id'
            . ' JOIN {issues} AS i ON i.issue_id = ch.issue_id'
            . ' JOIN {stamps} AS sc ON sc.stamp_id = ch.change_id'
            . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        $query .= ' WHERE fl.file_id = %1d';
        if ( !$principal->isAuthenticated() )
            $query .= ' AND p.is_public = 1';
        $query .= ' AND p.is_archived = 0';

        if ( !( $file = $this->connection->queryRow( $query, $fileId, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::NormalAccess ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownFile );

        if ( $flags & self::RequireAdministratorOrOwner && $file[ 'project_access' ] != System_Const::AdministratorAccess
             && $file[ 'user_id' ] != $principal->getUserId() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $file;
    }

    /**
    * Get the file as an attachment for download.
    * @param $fileId Identifier of the file.
    * @return A System_Core_Attachment object wrapping the file content
    * for download.
    */
    public function getAttachment( $fileId )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT fl.file_name, fl.file_size, fl.file_data, fl.file_storage'
            . ' FROM {files} AS fl'
            . ' JOIN {changes} AS ch ON ch.change_id = fl.file_id'
            . ' JOIN {issues} AS i ON i.issue_id = ch.issue_id'
            . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        $query .= ' WHERE fl.file_id = %1d';
        if ( !$principal->isAuthenticated() )
            $query .= ' AND p.is_public = 1';
        $query .= ' AND p.is_archived = 0';

        if ( !( $file = $this->connection->queryRow( $query, $fileId, $principal->getUserId() ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownFile );

        if ( $file[ 'file_storage' ] == self::DatabaseStorage )
            return $this->connection->createAttachment( $file[ 'file_data' ], $file[ 'file_size' ], $file[ 'file_name' ] );

        $path = $this->getAttachmentPath( $fileId );
        return System_Core_Attachment::fromFile( $path, $file[ 'file_size' ], $file[ 'file_name' ] );
    }

    /**
    * Get the issue description.
    * @param $issue The issue for which the description is retrieved.
    * @return An associative array representing the description.
    */
    public function getDescription( $issue )
    {
        $issueId = $issue[ 'issue_id' ];

        $query = 'SELECT id.descr_text, id.descr_format, i.issue_id, i.folder_id,'
            . ' s.user_id AS modified_user, s.stamp_time AS modified_date, u.user_name AS modified_by'
            . ' FROM {issue_descriptions} AS id'
            . ' JOIN {issues} AS i ON i.issue_id = id.issue_id'
            . ' JOIN {stamps} AS s ON s.stamp_id = i.descr_id'
            . ' JOIN {users} AS u ON u.user_id = s.user_id'
            . ' WHERE id.issue_id = %d';

        if ( !( $descr = $this->connection->queryRow( $query, $issueId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownDescription );

        return $descr;
    }

    /**
    * Return @c true if the description has been added or modified since the given stamp.
    */
    public function isDescriptionModified( $issue, $sinceStamp )
    {
        return $issue[ 'descr_id' ] != null && $issue[ 'descr_id' ] > $sinceStamp;
    }

    /**
    * Return @c true if the description has been deleted since the given stamp.
    */
    public function isDescriptionDeleted( $issue, $sinceStamp )
    {
        return $issue[ 'descr_id' ] == null && $sinceStamp > 0 && $issue[ 'descr_stub_id' ] > $sinceStamp;
    }

    /**
    * Create a new issue in the given folder.
    * @param $folder Folder into which the issue is added.
    * @param $name Name of the issue.
    * @param $values Array of initial values of attributes.
    * @return The identifier of the added issue.
    */
    public function addIssue( $folder, $name, $values )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $issueId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {issues} ( issue_id, folder_id, issue_name, stamp_id ) VALUES ( %d, %d, %s, %d )';
            $this->connection->execute( $query, $issueId, $folderId, $name, $issueId );

            $query = 'INSERT INTO {changes} ( change_id, issue_id, change_type, stamp_id, value_new ) VALUES ( %d, %d, %d, %d, %s )';
            $this->connection->execute( $query, $issueId, $issueId, System_Const::IssueCreated, $issueId, $name );

            $query = 'INSERT INTO {attr_values} ( issue_id, attr_id, attr_value ) VALUES ( %d, %d, %s )';
            foreach ( $values as $attributeId => $value ) {
                if ( $value != '' )
                    $this->connection->execute( $query, $issueId, $attributeId, $value );
            }

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $issueId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $issueId;
    }

    /**
    * Rename an issue.
    * @param $issue The issue to rename.
    * @param $newName The new name of the issue.
    * @return The identifier of the change or @c false if the name was
    * not modified.
    */
    public function renameIssue( $issue, $newName )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $issue[ 'issue_id' ];
        $folderId = $issue[ 'folder_id' ];
        $oldName = $issue[ 'issue_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {changes} ( change_id, issue_id, change_type, stamp_id, value_old, value_new ) VALUES ( %d, %d, %d, %d, %s, %s )';
            $this->connection->execute( $query, $stampId, $issueId, System_Const::IssueRenamed, $stampId, $oldName, $newName );

            $query = 'UPDATE {issues} SET issue_name = %s WHERE issue_id = %d';
            $this->connection->execute( $query, $newName, $issueId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Modify the value of an attribute for an issue.
    * @param $issue The issue to modify.
    * @param $attribute The attribute to modify.
    * @param $newValue The new value of the attribute.
    * @return The identifier of the change or @c false if the value was
    * not modified.
    */
    public function setValue( $issue, $attribute, $newValue )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $issue[ 'issue_id' ];
        $folderId = $issue[ 'folder_id' ];
        $attributeId = $attribute[ 'attr_id' ];
        $definition = $attribute[ 'attr_def' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'SELECT attr_value FROM {attr_values} WHERE issue_id = %d AND attr_id = %d';
            $oldValue = $this->connection->queryScalar( $query, $issueId, $attributeId );

            if ( System_Api_ValueHelper::areAttributeValuesEqual( $definition, $newValue, $oldValue ) ) {
                $transaction->commit();
                return false;
            }

            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            if ( $oldValue == '' )
                $query = 'INSERT INTO {attr_values} ( issue_id, attr_id, attr_value ) VALUES ( %1d, %2d, %3s )';
            else if ( $newValue == '' )
                $query = 'DELETE FROM {attr_values} WHERE issue_id = %1d AND attr_id = %2d';
            else
                $query = 'UPDATE {attr_values} SET attr_value = %3s WHERE issue_id = %1d AND attr_id = %2d';
            $this->connection->execute( $query, $issueId, $attributeId, $newValue );

            $query = 'INSERT INTO {changes} ( change_id, issue_id, change_type, stamp_id, attr_id, value_old, value_new )'
                . ' VALUES ( %d, %d, %d, %d, %d, %s, %s )';
            $this->connection->execute( $query, $stampId, $issueId, System_Const::ValueChanged, $stampId, $attributeId, $oldValue, $newValue );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Add a new comment to the issue.
    * @param $issue The issue to modify.
    * @param $text Content of the comment to add.
    * @param $format The format of the comment.
    * @return The identifier of the new comment.
    */
    public function addComment( $issue, $text, $format )
    {
        $principal = System_Api_Principal::getCurrent();

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $issueId = $issue[ 'issue_id' ];
            $folderId = $issue[ 'folder_id' ];

            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $commentId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {changes} ( change_id, issue_id, change_type, stamp_id ) VALUES ( %d, %d, %d, %d )';
            $this->connection->execute( $query, $commentId, $issueId, System_Const::CommentAdded, $commentId );

            $query = 'INSERT INTO {comments} ( comment_id, comment_text, comment_format ) VALUES ( %d, %s, %d )';
            $this->connection->execute( $query, $commentId, $text, $format );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $commentId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $commentId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $commentId;
    }

    /**
    * Add the attached file to the issue.
    * @param $issue The issue to modify.
    * @param $attachment The System_Core_Attachment object containing uploaded
    * file content.
    * @param $name Name of the file.
    * @param $description Optional description of the file.
    * @return The identifier of the new file.
    */
    public function addFile( $issue, $attachment, $name, $description )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( System_Core_Application::getInstance()->getSite()->getConfig( 'demo_mode' ) )
            $principal->checkAdministrator();

        $issueId = $issue[ 'issue_id' ];
        $folderId = $issue[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $fileId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {changes} ( change_id, issue_id, change_type, stamp_id ) VALUES ( %d, %d, %d, %d )';
            $this->connection->execute( $query, $fileId, $issueId, System_Const::FileAdded, $fileId );

            $storage = $this->getAttachmentStorage( $attachment );
            if ( $storage == self::DatabaseStorage ) {
                $query = 'INSERT INTO {files} ( file_id, file_name, file_size, file_data, file_descr, file_storage )'
                    . ' VALUES ( %d, %s, %d, %b, %s, %d )';
                $this->connection->execute( $query, $fileId, $name, $attachment->getSize(), $attachment, $description, $storage );
            } else {
                $path = $this->getAttachmentPath( $fileId, true );
                if ( !$attachment->saveAs( $path ) )
                    throw new System_Core_Exception( 'Cannot save attachment' );

                $query = 'INSERT INTO {files} ( file_id, file_name, file_size, file_data, file_descr, file_storage )'
                    . ' VALUES ( %d, %s, %d, NULL, %s, %d )';
                $this->connection->execute( $query, $fileId, $name, $attachment->getSize(), $description, $storage );
            }

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $fileId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $fileId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $fileId;
    }

    /**
    * Find the issue, comment or file.
    * @param $itemId Identifier of the item to find.
    * @return Identifier of the issue containing the given item.
    */
    public function findItem( $itemId )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT i.issue_id FROM {issues} AS i'
            . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        $query .= ' WHERE i.issue_id = %1d';
        if ( !$principal->isAuthenticated() )
            $query .= ' AND p.is_public = 1';
        $query .= ' AND p.is_archived = 0';

        if ( $issueId = $this->connection->queryScalar( $query, $itemId, $principal->getUserid() ) )
            return $issueId;

        $query = 'SELECT ch.issue_id FROM {changes} AS ch'
            . ' JOIN {issues} AS i ON i.issue_id = ch.issue_id'
            . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( $principal->isAuthenticated() && !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        $query .= ' WHERE ch.change_id = %1d AND ch.change_type >= %2d AND ch.change_type <= %3d';
        if ( !$principal->isAuthenticated() )
            $query .= ' AND p.is_public = 1';
        $query .= ' AND p.is_archived = 0';

        if ( $issueId = $this->connection->queryScalar( $query, $itemId, System_Const::CommentAdded, System_Const::FileAdded, $principal->getUserid() ) )
            return $issueId;

        throw new System_Api_Error( System_Api_Error::ItemNotFound );
    }

    /**
    * Delete the specified issue.
    * @param $issue The issue to delete.
    * @return @c The identifier of the issue stub.
    */
    public function deleteIssue( $issue )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $issue[ 'issue_id' ];
        $folderId = $issue[ 'folder_id' ];
        $previousId = $issue[ 'stub_id' ] != null ? $issue[ 'stub_id' ] : $issueId;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable );

        try {
            $query = 'SELECT fl.file_id FROM {files} AS fl'
                . ' JOIN {changes} ch ON ch.change_id = fl.file_id'
                . ' WHERE ch.issue_id = %d AND fl.file_storage = %d';
            $files = $this->connection->queryTable( $query, $issueId, self::FileSystemStorage );

            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {issue_stubs} ( stub_id, prev_id, issue_id, folder_id ) VALUES ( %d, %d, %d, %d )';
            $this->connection->execute( $query, $stampId, $previousId, $issueId, $folderId );

            $query = 'DELETE FROM {issues} WHERE issue_id = %d';
            $this->connection->execute( $query, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Deleted issue "%1" from folder "%2"', null, $issue[ 'issue_name' ], $issue[ 'folder_name' ] ) );

        $this->deleteFiles( $files );

        return $stampId;
    }

    /**
    * Move the issue to the specified folder.
    * @param $issue The issue to move.
    * @param $folder The folder to move the issue to.
    * @return @c The identifier of the change or @c null if the issue was
    * not moved.
    */
    public function moveIssue( $issue, $folder )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $issue[ 'issue_id' ];
        $fromFolderId = $issue[ 'folder_id' ];
        $previousId = $issue[ 'stub_id' ] != null ? $issue[ 'stub_id' ] : $issueId;

        $toFolderId = $folder[ 'folder_id' ];

        if ( $fromFolderId == $toFolderId )
            return null;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {issue_stubs} ( stub_id, prev_id, issue_id, folder_id ) VALUES ( %d, %d, %d, %d )';
            $this->connection->execute( $query, $stampId, $previousId, $issueId, $fromFolderId );

            $query = 'INSERT INTO {changes} ( change_id, issue_id, change_type, stamp_id, from_folder_id, to_folder_id ) VALUES ( %d, %d, %d, %d, %d, %d )';
            $this->connection->execute( $query, $stampId, $issueId, System_Const::IssueMoved, $stampId, $fromFolderId, $toFolderId );

            $query = 'UPDATE {issues} SET folder_id = %1d, stub_id = %2d WHERE issue_id = %3d AND COALESCE( stub_id, 0 ) < %2d';
            $this->connection->execute( $query, $toFolderId, $stampId, $issueId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE ( folder_id = %2d OR folder_id = %3d ) AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $fromFolderId, $toFolderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Edit the specified comment.
    * @param $comment The comment to edit.
    * @param $newText The new text of the comment.
    * @param $newFormat The new format of the comment.
    * @return @c The identifier of the change or @c null if the comment was
    * not changed.
    */
    public function editComment( $comment, $newText, $newFormat )
    {
        $principal = System_Api_Principal::getCurrent();

        $commentId = $comment[ 'comment_id' ];
        $issueId = $comment[ 'issue_id' ];
        $folderId = $comment[ 'folder_id' ];
        $oldText = $comment[ 'comment_text' ];
        $oldFormat = $comment[ 'comment_format' ];

        if ( $newText == $oldText && $newFormat == $oldFormat )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'UPDATE {comments} SET comment_text = %s, comment_format = %d WHERE comment_id = %d';
            $this->connection->execute( $query, $newText, $newFormat, $commentId );

            $query = 'UPDATE {changes} SET stamp_id = %1d WHERE change_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $commentId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Delete the specified comment.
    * @param $comment The comment to delete.
    * @return @c The identifier of the comment stub.
    */
    public function deleteComment( $comment )
    {
        $principal = System_Api_Principal::getCurrent();

        $commentId = $comment[ 'comment_id' ];
        $issueId = $comment[ 'issue_id' ];
        $folderId = $comment[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {change_stubs} ( stub_id, change_id, issue_id ) VALUES ( %d, %d, %d )';
            $this->connection->execute( $query, $stampId, $commentId, $issueId );

            $query = 'DELETE FROM {changes} WHERE change_id = %d';
            $this->connection->execute( $query, $commentId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Edit the specified file.
    * @param $file The file to edit.
    * @param $newName The new name of the file.
    * @param $newDescription The new description of the file.
    * @return @c The identifier of the change or @c null if the file was
    * not changed.
    */
    public function editFile( $file, $newName, $newDescription )
    {
        $principal = System_Api_Principal::getCurrent();

        $fileId = $file[ 'file_id' ];
        $issueId = $file[ 'issue_id' ];
        $folderId = $file[ 'folder_id' ];
        $oldName = $file[ 'file_name' ];
        $oldDescription = $file[ 'file_descr' ];

        if ( $newName == $oldName && $newDescription == $oldDescription )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'UPDATE {files} SET file_name = %s, file_descr = %s WHERE file_id = %d';
            $this->connection->execute( $query, $newName, $newDescription, $fileId );

            $query = 'UPDATE {changes} SET stamp_id = %1d WHERE change_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $fileId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Delete the specified file.
    * @param $file The file to delete.
    * @return @c The identifier of the file stub.
    */
    public function deleteFile( $file )
    {
        $principal = System_Api_Principal::getCurrent();

        $fileId = $file[ 'file_id' ];
        $issueId = $file[ 'issue_id' ];
        $folderId = $file[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {change_stubs} ( stub_id, change_id, issue_id ) VALUES ( %d, %d, %d )';
            $this->connection->execute( $query, $stampId, $fileId, $issueId );

            $query = 'DELETE FROM {changes} WHERE change_id = %d';
            $this->connection->execute( $query, $fileId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        if ( $file[ 'file_storage' ] == self::FileSystemStorage )
            @unlink( $this->getAttachmentPath( $fileId ) );

        return $stampId;
    }

    /**
    * Add a description to the issue.
    * @param $issue The issue to modify.
    * @param $text Content of the description.
    * @param $format The format of the description.
    * @return The identifier of the change.
    */
    public function addDescription( $issue, $text, $format )
    {
        if ( $issue[ 'descr_id' ] != null )
            throw new System_Api_Error( System_Api_Error::DescriptionAlreadyExists );

        $principal = System_Api_Principal::getCurrent();

        $issueId = $issue[ 'issue_id' ];
        $folderId = $issue[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'INSERT INTO {issue_descriptions} ( issue_id, descr_text, descr_format ) VALUES ( %d, %s, %d )';
            $this->connection->execute( $query, $issueId, $text, $format );

            $query = 'UPDATE {issues} SET descr_id = %1d WHERE issue_id = %2d AND COALESCE( descr_id, descr_stub_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Edit the existing description of the issue.
    * @param $descr The description to modify.
    * @param $newText Content of the description.
    * @param $newFormat The format of the description.
    * @return The identifier of the change or @c null if no change was made.
    */
    public function editDescription( $descr, $newText, $newFormat )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $descr[ 'issue_id' ];
        $folderId = $descr[ 'folder_id' ];
        $oldText = $descr[ 'descr_text' ];
        $oldFormat = $descr[ 'descr_format' ];

        if ( $newText == $oldText && $newFormat == $oldFormat )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'UPDATE {issue_descriptions} SET descr_text = %s, descr_format = %d  WHERE issue_id = %d';
            $this->connection->execute( $query, $newText, $newFormat, $issueId );

            $query = 'UPDATE {issues} SET descr_id = %1d WHERE issue_id = %2d AND COALESCE( descr_id, descr_stub_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Delete the description of the issue.
    * @param $descr The description to delete.
    * @return The identifier of the change.
    */
    public function deleteDescription( $descr )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $descr[ 'issue_id' ];
        $folderId = $descr[ 'folder_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::ReadCommitted );

        try {
            $query = 'INSERT INTO {stamps} ( user_id, stamp_time ) VALUES ( %d, %d )';
            $this->connection->execute( $query, $principal->getUserId(), time() );
            $stampId = $this->connection->getInsertId( 'stamps', 'stamp_id' );

            $query = 'DELETE FROM {issue_descriptions} WHERE issue_id = %d';
            $this->connection->execute( $query, $issueId );

            $query = 'UPDATE {issues} SET descr_id = NULL, descr_stub_id = %1d WHERE issue_id = %2d AND COALESCE( descr_id, descr_stub_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {issues} SET stamp_id = %1d WHERE issue_id = %2d AND stamp_id < %1d';
            $this->connection->execute( $query, $stampId, $issueId );

            $query = 'UPDATE {folders} SET stamp_id = %1d WHERE folder_id = %2d AND COALESCE( stamp_id, 0 ) < %1d';
            $this->connection->execute( $query, $stampId, $folderId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stampId;
    }

    /**
    * Delete files from the attachment store.
    */
    public function deleteFiles( $files )
    {
        foreach ( $files as $file )
            @unlink( $this->getAttachmentPath( $file[ 'file_id' ] ) );
    }

    /**
    * Return @c true if any attachments are stored in the file system.
    */
    public function checkFileSystemFiles()
    {
        $query = 'SELECT COUNT(*) FROM {files} WHERE file_storage = %d';

        return $this->connection->queryScalar( $query, self::FileSystemStorage ) > 0;
    }

    private function getAttachmentPath( $id, $createDir = false )
    {
        $site = System_Core_Application::getInstance()->getSite();
        $siteDir = $site->getPath( 'site_dir' );

        $subDir = sprintf( '%s/storage/%03d', $siteDir, $id / 1000000 );
        $subSubDir = sprintf( '%s/%03d', $subDir, ( $id / 1000 ) % 1000 );
        $file = sprintf( '%s/%03d', $subSubDir, $id % 1000 );

        if ( $createDir && !is_dir( $subSubDir ) )
            mkdir( $subSubDir, 0755, true );

        return $file;
    }

    private function getAttachmentStorage( $attachment )
    {
        $serverManager = new System_Api_ServerManager();
        $maxSize = $serverManager->getSetting( 'file_db_max_size' );

        if ( $attachment->getSize() <= $maxSize )
            return self::DatabaseStorage;

        return self::FileSystemStorage;
    }
}
