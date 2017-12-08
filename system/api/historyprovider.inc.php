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
* Extract the history of an issue from the database.
*
* This class can be used to build SQL queries for retrieving the issue history
* which appropriate paging and sorting. Queries can be executed using appropriate
* methods of System_Db_Connection with arguments provided by getQueryArguments().
*
* Issue history consists of changes, comments and files sorted by date.
* Changes made by the same user within a short period of time are grouped
* together.
*/
class System_Api_HistoryProvider
{
    /**
    * Sort in ascending order.
    */
    const Ascending = 'asc';
    /**
    * Sort in descending order.
    */
    const Descending = 'desc';

    /**
    * Display all changes.
    */
    const AllHistory = 1;
    /**
    * Display only comments.
    */
    const Comments = 2;
    /**
    * Display only attachments.
    */
    const Files = 3;
    /**
    * Display comments and attachments.
    */
    const CommentsAndFiles = 4;

    private $issueId = 0;
    private $sinceStamp = null;
    private $exceptOwn = false;
    private $exceptSubscriptionId = null;

    private $arguments = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Set the identifier of the issue.
    */
    public function setIssueId( $issueId )
    {
        $this->issueId = $issueId;
    }

    /**
    * Only include changes with stamp greater than specified value.
    */
    public function setSinceStamp( $stamp )
    {
        $this->sinceStamp = $stamp;
    }

    /**
    * Exclude changes made by the current user.
    */
    public function setExceptOwnChanges()
    {
        $this->exceptOwn = true;
    }

    /**
    * Exclude changes related to the given subscription.
    */
    public function setExceptSubscriptionId( $subscriptionId )
    {
        $this->exceptSubscriptionId = $subscriptionId;
    }

    /**
    * Return a query for calculating the number of items.
    * @param $itemType The type of history items.
    */
    public function generateCountQuery( $itemType )
    {
        $principal = System_Api_Principal::getCurrent();

        $this->arguments = array( $this->issueId, System_Const::CommentAdded, System_Const::FileAdded, $principal->getUserId(), $this->sinceStamp, $this->exceptSubscriptionId );

        $query = 'SELECT COUNT(*) FROM {changes} AS ch';
        if ( $this->exceptOwn )
            $query .= ' JOIN {stamps} AS sc ON sc.stamp_id = ch.change_id';
        $query .= ' WHERE ch.issue_id = %1d';
        if ( $itemType == self::CommentsAndFiles )
            $query .= ' AND ( ch.change_type = %2d OR ch.change_type = %3d )';
        else if ( $itemType == self::Comments )
            $query .= ' AND ch.change_type = %2d';
        else if ( $itemType == self::Files )
            $query .= ' AND ch.change_type = %3d';
        if ( $this->sinceStamp != null )
            $query .= ' AND ch.change_id > %5d';
        if ( $this->exceptOwn )
            $query .= ' AND sc.user_id <> %4d';
        if ( $this->exceptSubscriptionId != null )
            $query .= ' AND COALESCE( ch.subscription_id, 0 ) <> %6d';

        return $query;
    }

    /**
    * Return a query for extracting item identifiers only.
    * @param $itemType The type of history items.
    */
    public function generateSimpleSelectQuery( $itemType )
    {
        $this->arguments = array( $this->issueId, System_Const::CommentAdded, System_Const::FileAdded );

        $query = 'SELECT ch.change_id FROM {changes} AS ch WHERE ch.issue_id = %1d';

        if ( $itemType == self::CommentsAndFiles )
            $query .= ' AND ( ch.change_type = %2d OR ch.change_type = %3d )';
        else if ( $itemType == self::Comments )
            $query .= ' AND ch.change_type = %2d';
        else if ( $itemType == self::Files )
            $query .= ' AND ch.change_type = %3d';

        return $query;
    }

    /**
    * Return a query for extracting item details.
    * @param $itemType The type of history items.
    */
    public function generateSelectQuery( $itemType )
    {
        $principal = System_Api_Principal::getCurrent();

        $this->arguments = array( $this->issueId, System_Const::CommentAdded, System_Const::FileAdded, $principal->getUserId(), $this->sinceStamp, $this->exceptSubscriptionId );

        $query = 'SELECT ch.change_id, ch.change_type, ch.stamp_id,'
            . ' sc.stamp_time AS created_date, uc.user_id AS created_user, uc.user_name AS created_by,'
            . ' sm.stamp_time AS modified_date, um.user_id AS modified_user, um.user_name AS modified_by';
        if ( $itemType == self::AllHistory ) {
            $query .= ', ch.attr_id, ch.value_old, ch.value_new, a.attr_name, a.attr_def,'
                . ' fp.project_name AS from_project_name, ff.folder_name AS from_folder_name,'
                . ' tp.project_name AS to_project_name, tf.folder_name AS to_folder_name';
        }
        if ( $itemType == self::AllHistory || $itemType == self::Comments || $itemType == self::CommentsAndFiles )
            $query .= ', c.comment_text, c.comment_format';
        if ( $itemType == self::AllHistory || $itemType == self::Files || $itemType == self::CommentsAndFiles )
            $query .= ', f.file_name, f.file_size, f.file_descr';
        $query .= ' FROM {changes} AS ch'
            . ' JOIN {stamps} AS sc ON sc.stamp_id = ch.change_id'
            . ' JOIN {users} AS uc ON uc.user_id = sc.user_id'
            . ' JOIN {stamps} AS sm ON sm.stamp_id = ch.stamp_id'
            . ' JOIN {users} AS um ON um.user_id = sm.user_id';
        if ( $itemType == self::AllHistory ) {
            $query .= ' LEFT OUTER JOIN {attr_types} AS a ON a.attr_id = ch.attr_id'
                . ' LEFT OUTER JOIN {folders} AS ff ON ff.folder_id = ch.from_folder_id'
                . ' LEFT OUTER JOIN {projects} AS fp ON fp.project_id = ff.project_id';
            if ( !$principal->isAuthenticated() )
                $query .= ' AND fp.is_public = 1';
            else if ( !$principal->isAdministrator() )
                $query .= ' AND fp.project_id IN ( SELECT project_id FROM {effective_rights} WHERE user_id = %4d )';
            $query .= ' LEFT OUTER JOIN {folders} AS tf ON tf.folder_id = ch.to_folder_id'
                . ' LEFT OUTER JOIN {projects} AS tp ON tp.project_id = tf.project_id';
            if ( !$principal->isAuthenticated() )
                $query .= ' AND tp.is_public = 1';
            else if ( !$principal->isAdministrator() )
                $query .= ' AND tp.project_id IN ( SELECT project_id FROM {effective_rights} WHERE user_id = %4d )';
        }
        if ( $itemType == self::AllHistory || $itemType == self::Comments || $itemType == self::CommentsAndFiles ) {
            if ( $itemType == self::AllHistory || $itemType == self::CommentsAndFiles )
                $query .= ' LEFT OUTER';
            $query .= ' JOIN {comments} AS c ON c.comment_id = ch.change_id AND ch.change_type = %2d';
        }
        if ( $itemType == self::AllHistory || $itemType == self::Files || $itemType == self::CommentsAndFiles ) {
            if ( $itemType == self::AllHistory || $itemType == self::CommentsAndFiles )
                $query .= ' LEFT OUTER';
            $query .= ' JOIN {files} AS f ON f.file_id = ch.change_id AND ch.change_type = %3d';
        }
        $query .= ' WHERE ch.issue_id = %1d';
        if ( $itemType == self::CommentsAndFiles )
            $query .= ' AND ( ch.change_type = %2d OR ch.change_type = %3d )';
        if ( $this->sinceStamp != null )
            $query .= ' AND ch.change_id > %5d';
        if ( $this->exceptOwn )
            $query .= ' AND sc.user_id <> %4d';
        if ( $this->exceptSubscriptionId != null )
            $query .= ' AND COALESCE( ch.subscription_id, 0 ) <> %6d';

        return $query;
    }

    /**
    * Return the arguments to be passed when executing the query.
    */
    public function getQueryArguments()
    {
        return $this->arguments;
    }

    /**
    * Return the sorting order specifier. Items are sorted by creation date
    * according to the specified order.
    */
    public function getOrderBy( $order )
    {
        if ( $order == self::Ascending )
            return 'ch.change_id ASC';
        else if ( $order == self::Descending )
            return 'ch.change_id DESC';

        throw new System_Core_Exception( 'Invalid sort order' );
    }

    /**
    * Process a page of items to group changes together.
    * @param $page The page of rows returned from the database.
    * @return Items with changes made by the same user within a short period
    * of time grouped together.
    */
    public function processPage( $page )
    {
        $items = array();

        $change = null;

        foreach ( $page as $row ) {
            if ( $row[ 'change_type' ] <= System_Const::ValueChanged && $change != null ) {
                if ( $row[ 'created_user' ] == $change[ 'changes' ][ 0 ][ 'created_user' ]
                     && ( $row[ 'created_date' ] - $change[ 'changes' ][ 0 ][ 'created_date' ] ) < 180 ) {
                    $change[ 'changes' ][] = $row;
                    continue;
                }
            }

            if ( $change != null ) {
                $items[ $change[ 'change_id' ] ] = $change;
                $change = null;
            }

            if ( $row[ 'change_type' ] <= System_Const::ValueChanged ) {
                $change = $row;
                $change[ 'changes' ][ 0 ] = $row;
            } else {
                $items[ $row[ 'change_id' ] ] = $row;
            }
        }

        if ( $change != null )
            $items[ $change[ 'change_id' ] ] = $change;

        return $items;
    }
}
