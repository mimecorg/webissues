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
* Manage issue states for current user.
*
* Issue states have a incrementally growing identifier. All changes require
* deleting the old state and inserting a new state.
*
* Like all API classes, this class does not check permissions to perform
* an operation and does not validate the input values. An error is thrown
* only if the requested object does not exist or is inaccessible.
*/
class System_Api_StateManager extends System_Api_Base
{
    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get list of all issue states for current user.
    * @param $sinceState Last state used for incremental updates.
    * @return An array of associative arrays representing issue states.
    */
    public function getStates( $sinceState )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT s.state_id, s.issue_id, s.read_id, s.subscription_id'
            . ' FROM {issue_states} AS s'
            . ' JOIN {issues} AS i ON i.issue_id = s.issue_id'
            . ' JOIN {folders} AS f ON f.folder_id = i.folder_id'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %1d';
        $query .= ' WHERE s.user_id = %1d AND s.state_id > %2d AND p.is_archived = 0';

        return $this->connection->queryTable( $query, $principal->getUserId(), $sinceState );
    }

    /**
    * Set the read state of the issue.
    * @param $issue The issue to modify.
    * @param $readId The new read stamp of the issue.
    * @return The identifier of the state or @c false if the state was
    * not modified.
    */
    public function setIssueRead( $issue, $readId )
    {
        $principal = System_Api_Principal::getCurrent();

        $issueId = $issue[ 'issue_id' ];
        $stampId = $issue[ 'stamp_id' ];
        $stateId = $issue[ 'state_id' ];

        if ( $readId < 0 || $readId > $stampId )
            throw new System_Api_Error( System_Api_Error::InvalidValue );

        if ( $readId == $issue[ 'read_id' ] )
            return false;

        $transaction = $this->connection->beginTransaction();

        try {
            if ( $stateId != null ) {
                $query = 'DELETE FROM {issue_states} WHERE state_id = %d';
                $this->connection->execute( $query, $stateId );
            }

            $query = 'INSERT INTO {issue_states} ( user_id, issue_id, read_id, subscription_id )'
                . ' SELECT %1d AS user_id, i.issue_id, %3d? AS read_id, s.subscription_id'
                . ' FROM {issues} AS i'
                . ' LEFT OUTER JOIN {subscriptions} AS s ON s.issue_id = i.issue_id AND s.user_id = %1d'
                . ' WHERE i.issue_id = %2d';

            $this->connection->execute( $query, $principal->getUserId(), $issueId, $readId );

            $stateId = $this->connection->getInsertId( 'issue_states', 'state_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $stateId;
    }

    /**
    * Set the read state of all issues in specified folder.
    * @param $folder Folder containing issues to modify.
    * @param $readId The new read stamp of the folder.
    */
    public function setFolderRead( $folder, $readId )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];
        $stampId = $folder[ 'stamp_id' ];

        if ( $readId < 0 || $readId > $stampId )
            throw new System_Api_Error( System_Api_Error::InvalidValue );

        $transaction = $this->connection->beginTransaction();

        try {
            $query = 'DELETE FROM {issue_states}'
                . ' WHERE user_id = %d'
                . ' AND issue_id IN ( SELECT issue_id FROM {issues} WHERE folder_id = %d )';

            $this->connection->execute( $query, $principal->getUserId(), $folderId );

            $query = 'INSERT INTO {issue_states} ( user_id, issue_id, read_id, subscription_id )'
                . ' SELECT %1d AS user_id, i.issue_id, %3d? AS read_id, s.subscription_id'
                . ' FROM {issues} AS i'
                . ' LEFT OUTER JOIN {subscriptions} AS s ON s.issue_id = i.issue_id AND s.user_id = %1d'
                . ' WHERE i.folder_id = %2d';

            $this->connection->execute( $query, $principal->getUserId(), $folderId, $readId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return true;
    }
}
