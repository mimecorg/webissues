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
* Manage alerts.
*
* Like all API classes, this class does not check permissions to perform
* an operation and does not validate the input values. An error is thrown
* only if the requested object does not exist or is inaccessible.
*/
class System_Api_AlertManager extends System_Api_Base
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Permission to edit the alert is required. */
    const AllowEdit = 1;
    /** Indicate a public alert. */
    const IsPublic = 2;
    /*@}*/

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Return all alerts for the current user.
    * @return An array of associative arrays representing alerts.
    */
    public function getAlerts()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, a.folder_id, a.type_id, a.view_id, a.alert_email, a.summary_days, a.summary_hours, ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
            . ' FROM {alerts} AS a'
            . ' WHERE ( a.user_id = %1d OR a.user_id IS NULL )'
            . ' AND ( a.type_id IS NOT NULL OR EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %1d';
        $query .= ' WHERE f.folder_id = a.folder_id AND p.is_archived = 0 ) )';

        return $this->connection->queryTable( $query, $principal->getUserId() );
    }

    /**
    * Get the total number of alerts for given folder.
    * @param $folder Folder for which alerts are retrieved.
    */
    public function getAlertsCount( $folder )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT COUNT(*)'
            . ' FROM {alerts}'
            . ' WHERE ( user_id = %d OR user_id IS NULL ) AND folder_id = %d';

        return $this->connection->queryScalar( $query, $principal->getUserId(), $folderId );
    }

    /**
    * Get the paged list of alerts for given folder.
    * @param $folder Folder for which alerts are retrieved.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing alerts.
    */
    public function getAlertsPage( $folder, $orderBy, $limit, $offset )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];

        $query = 'SELECT a.alert_id, v.view_id, v.view_name, v.view_def, a.alert_email, ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
            . ' FROM {alerts} AS a'
            . ' LEFT OUTER JOIN {views} AS v ON v.view_id = a.view_id'
            . ' WHERE ( a.user_id = %d OR a.user_id IS NULL ) AND a.folder_id = %d';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $principal->getUserId(), $folderId );
    }

    /**
    * Get the total number of alerts for given issue type.
    * @param $type Issue type for which alerts are retrieved.
    */
    public function getGlobalAlertsCount( $type )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        $query = 'SELECT COUNT(*)'
            . ' FROM {alerts}'
            . ' WHERE ( user_id = %d OR user_id IS NULL ) AND type_id = %d';

        return $this->connection->queryScalar( $query, $principal->getUserId(), $typeId );
    }

    /**
    * Get the paged list of alerts for given issue type.
    * @param $type Issue type for which alerts are retrieved.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing alerts.
    */
    public function getGlobalAlertsPage( $type, $orderBy, $limit, $offset )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        $query = 'SELECT a.alert_id, v.view_id, v.view_name, v.view_def, a.alert_email, ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
            . ' FROM {alerts} AS a'
            . ' LEFT OUTER JOIN {views} AS v ON v.view_id = a.view_id'
            . ' WHERE ( a.user_id = %d OR a.user_id IS NULL ) AND a.type_id = %d';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $principal->getUserId(), $typeId );
    }

    /**
    * Check if given folder has the "All Issues" alert.
    * @param $folder Folder for which alert is retrieved.
    * @param $flags If IsPublic is passed, only public alerts are taken into account.
    * @return @c true if the folder has the "All Issues" view.
    */
    public function hasAllIssuesAlert( $folder, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];

        if ( $flags & self::IsPublic )
            $query = 'SELECT alert_id FROM {alerts} WHERE user_id IS NULL AND folder_id = %2d AND view_id IS NULL';
        else
            $query = 'SELECT alert_id FROM {alerts} WHERE ( user_id = %1d OR user_id IS NULL ) AND folder_id = %2d AND view_id IS NULL';

        return $this->connection->queryScalar( $query, $principal->getUserId(), $folderId ) !== false;
    }

    /**
    * Get views for which there is no alert for a given folder.
    * @param $folder Folder for which views are retrieved.
    * @param $flags If IsPublic is passed, only public alerts are taken into account.
    * @return An array of associative arrays representing views.
    */
    public function getViewsWithoutAlerts( $folder, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];
        $typeId = $folder[ 'type_id' ];

        if ( $flags & self::IsPublic ) {
            $query = 'SELECT v.view_id, v.view_name, 1 AS is_public'
                . ' FROM {views} AS v'
                . ' LEFT OUTER JOIN {alerts} AS a ON a.view_id = v.view_id AND a.user_id IS NULL AND a.folder_id = %2d'
                . ' WHERE v.type_id = %3d AND v.user_id IS NULL AND a.alert_id IS NULL';
        } else {
            $query = 'SELECT v.view_id, v.view_name, ( CASE WHEN v.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
                . ' FROM {views} AS v'
                . ' LEFT OUTER JOIN {alerts} AS a ON a.view_id = v.view_id AND ( a.user_id = %1d OR a.user_id IS NULL ) AND a.folder_id = %2d'
                . ' WHERE v.type_id = %3d AND ( v.user_id = %1d OR v.user_id IS NULL ) AND a.alert_id IS NULL';
        }
        $query .= ' ORDER BY v.view_name COLLATE LOCALE';

        $views = $this->connection->queryTable( $query, $principal->getUserId(), $folderId, $typeId );

        $result = array();
        foreach ( $views as $view )
            $result[ $view[ 'is_public' ] ][ $view[ 'view_id' ] ] = $view[ 'view_name' ];

        return $result;
    }

    /**
    * Check if given issue type has the "All Issues" alert.
    * @param $folder Folder for which alert is retrieved.
    * @param $flags If IsPublic is passed, only public alerts are taken into account.
    * @return @c true if the issue type has the "All Issues" view.
    */
    public function hasAllIssuesGlobalAlert( $type, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        if ( $flags & self::IsPublic )
            $query = 'SELECT alert_id FROM {alerts} WHERE user_id IS NULL AND type_id = %2d AND view_id IS NULL';
        else
            $query = 'SELECT alert_id FROM {alerts} WHERE ( user_id = %1d OR user_id IS NULL ) AND type_id = %2d AND view_id IS NULL';

        return $this->connection->queryScalar( $query, $principal->getUserId(), $typeId ) !== false;
    }

    /**
    * Get views for which there is no alert for a given issue type.
    * @param $type Issue type for which views are retrieved.
    * @param $flags If IsPublic is passed, only public alerts are taken into account.
    * @return An array of associative arrays representing views.
    */
    public function getViewsWithoutGlobalAlerts( $type, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        if ( $flags & self::IsPublic ) {
            $query = 'SELECT v.view_id, v.view_name, 1 AS is_public'
                . ' FROM {views} AS v'
                . ' LEFT OUTER JOIN {alerts} AS a ON a.view_id = v.view_id AND a.user_id IS NULL AND a.type_id = %2d'
                . ' WHERE v.type_id = %2d AND v.user_id IS NULL AND a.alert_id IS NULL';
        } else {
            $query = 'SELECT v.view_id, v.view_name, ( CASE WHEN v.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
                . ' FROM {views} AS v'
                . ' LEFT OUTER JOIN {alerts} AS a ON a.view_id = v.view_id AND ( a.user_id = %1d OR a.user_id IS NULL ) AND a.type_id = %2d'
                . ' WHERE v.type_id = %2d AND ( v.user_id = %1d OR v.user_id IS NULL ) AND a.alert_id IS NULL';
        }
        $query .= ' ORDER BY v.view_name COLLATE LOCALE';

        $views = $this->connection->queryTable( $query, $principal->getUserId(), $typeId );

        $result = array();
        foreach ( $views as $view )
            $result[ $view[ 'is_public' ] ][ $view[ 'view_id' ] ] = $view[ 'view_name' ];

        return $result;
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getAlertsColumns()
    {
        return array(
            'name' => 'view_name COLLATE LOCALE'
        );
    }

    /**
    * Get the alert with given identifier.
    * @param $alertId Identifier of the alert.
    * @param $flags If AllowEdit is passed an error is thrown if the user
    * does not have permission to edit the alert.
    * @return Array representing the alert.
    */
    public function getAlert( $alertId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( ( $flags & self::AllowEdit ) && !$principal->isAdministrator() ) {
            $query = 'SELECT a.alert_id, v.view_id, v.view_name, a.alert_email, a.summary_days, a.summary_hours, ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public, r.project_access'
                . ' FROM {alerts} AS a'
                . ' LEFT OUTER JOIN {folders} AS f ON f.folder_id = a.folder_id'
                . ' LEFT OUTER JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        } else {
            $query = 'SELECT a.alert_id, v.view_id, v.view_name, a.alert_email, a.summary_days, a.summary_hours, ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
                . ' FROM {alerts} AS a';
        }
        $query .= ' LEFT OUTER JOIN {views} AS v ON v.view_id = a.view_id'
            . ' WHERE a.alert_id = %1d AND ( a.user_id = %2d OR a.user_id IS NULL )'
            . ' AND ( a.type_id IS NOT NULL OR EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %2d';
        $query .= ' WHERE f.folder_id = a.folder_id AND p.is_archived = 0 ) )';

        if ( !( $alert = $this->connection->queryRow( $query, $alertId, $principal->getUserId() ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownAlert );

        if ( ( $flags & self::AllowEdit ) && !$principal->isAdministrator() && $alert[ 'is_public' ] && $alert[ 'project_access' ] != System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $alert;
    }

    /**
    * Create a new alert. An error is thrown if such alert already exists.
    * @param $folder Folder for which the alert is created.
    * @param $view Optional view associated with the alert.
    * @param $alertEmail Type of emails associated with the alert.
    * @param $summaryDays List of days of week on which summary emails are sent.
    * @param $summaryHours List of hours at which summary emails are sent.
    * @param $flags If IsPublic is passed, a public alert is created.
    * @return The identifier of the new alert.
    */
    public function addAlert( $folder, $view, $alertEmail, $summaryDays, $summaryHours, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $folderId = $folder[ 'folder_id' ];
        $stampId = $folder[ 'stamp_id' ];
        $viewId = ( $view != null ) ? $view[ 'view_id' ] : null;
        $userId = ( $flags & self::IsPublic ) ? null : $principal->getUserId();

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'alerts' );

        try {
            if ( $flags & self::IsPublic )
                $query = 'SELECT alert_id FROM {alerts} WHERE user_id IS NULL AND folder_id = %2d AND view_id = %3d?';
            else
                $query = 'SELECT alert_id FROM {alerts} WHERE ( user_id = %1d OR user_id IS NULL ) AND folder_id = %2d AND view_id = %3d?';
            if ( $this->connection->queryScalar( $query, $userId, $folderId, $viewId ) !== false )
                throw new System_Api_Error( System_Api_Error::AlertAlreadyExists );

            $query = 'INSERT INTO {alerts} ( user_id, folder_id, type_id, view_id, alert_email, summary_days, summary_hours, stamp_id ) VALUES ( %d?, %d, NULL, %d?, %d, %s?, %s?, %d? )';
            $this->connection->execute( $query, $userId, $folderId, $viewId, $alertEmail, $summaryDays, $summaryHours, $stampId );
            $alertId = $this->connection->getInsertId( 'alerts', 'alert_id' );

            if ( $flags & self::IsPublic ) {
                $query = 'DELETE FROM {alerts} WHERE user_id IS NOT NULL AND folder_id = %d AND view_id = %d?';
                $this->connection->execute( $query, $folderId, $viewId );
            }

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $alertId;
    }

    /**
    * Create a new global alert. An error is thrown if such alert already exists.
    * @param $type Issue type for which the alert is created.
    * @param $view Optional view associated with the alert.
    * @param $alertEmail Type of emails associated with the alert.
    * @param $summaryDays List of days of week on which summary emails are sent.
    * @param $summaryHours List of hours at which summary emails are sent.
    * @param $flags If IsPublic is passed, a public alert is created.
    * @return The identifier of the new alert.
    */
    public function addGlobalAlert( $type, $view, $alertEmail, $summaryDays, $summaryHours, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];
        $viewId = ( $view != null ) ? $view[ 'view_id' ] : null;
        $userId = ( $flags & self::IsPublic ) ? null : $principal->getUserId();

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'alerts' );

        try {
            $query = 'SELECT MAX( stamp_id ) FROM {folders} WHERE type_id = %d';
            $stampId = $this->connection->queryScalar( $query, $typeId );

            if ( $flags & self::IsPublic )
                $query = 'SELECT alert_id FROM {alerts} WHERE user_id IS NULL AND type_id = %2d AND view_id = %3d?';
            else
                $query = 'SELECT alert_id FROM {alerts} WHERE ( user_id = %1d OR user_id IS NULL ) AND type_id = %2d AND view_id = %3d?';
            if ( $this->connection->queryScalar( $query, $userId, $typeId, $viewId ) !== false )
                throw new System_Api_Error( System_Api_Error::AlertAlreadyExists );

            $query = 'INSERT INTO {alerts} ( user_id, folder_id, type_id, view_id, alert_email, summary_days, summary_hours, stamp_id ) VALUES ( %d?, NULL, %d, %d?, %d, %s?, %s?, %d? )';
            $this->connection->execute( $query, $userId, $typeId, $viewId, $alertEmail, $summaryDays, $summaryHours, $stampId );
            $alertId = $this->connection->getInsertId( 'alerts', 'alert_id' );

            if ( $flags & self::IsPublic ) {
                $query = 'DELETE FROM {alerts} WHERE user_id IS NOT NULL AND type_id = %d AND view_id = %d?';
                $this->connection->execute( $query, $typeId, $viewId );
            }

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $alertId;
    }

    /**
    * Modify settings of an alert.
    * @param $alert The alert to modify.
    * @param $alertEmail Type of emails associated with the alert.
    * @param $summaryDays List of days of week on which summary emails are sent.
    * @param $summaryHours List of hours at which summary emails are sent.
    * @return @c true if the alert was modified.
    */
    public function modifyAlert( $alert, $alertEmail, $summaryDays, $summaryHours )
    {
        $alertId = $alert[ 'alert_id' ];
        $oldEmail = $alert[ 'alert_email' ];
        $oldDays = $alert[ 'summary_days' ];
        $oldHours = $alert[ 'summary_hours' ];

        if ( $alertEmail == $oldEmail && $summaryDays == $oldDays && $summaryHours == $oldHours )
            return false;

        $query = 'UPDATE {alerts} SET alert_email = %d, summary_days = %s!, summary_hours = %s! WHERE alert_id = %d';
        $this->connection->execute( $query, $alertEmail, $summaryDays, $summaryHours, $alertId );

        return true;
    }

    /**
    * Delete an alert.
    * @param $alert The alert to delete.
    * @return @c true if the alert was deleted.
    */
    public function deleteAlert( $alert )
    {
        $alertId = $alert[ 'alert_id' ];

        $query = 'DELETE FROM {alerts} WHERE alert_id = %d';
        $this->connection->execute( $query, $alertId );

        return true;
    }

    /**
    * Return alerts for which emails should be sent.
    * @param $includeSummary If @c true, the summary notifications and reports are
    * included in addition to immediate notifications.
    * @return An array of associative arrays representing alerts.
    */
    public function getAlertsToEmail( $includeSummary )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, a.folder_id, a.type_id, a.view_id, a.alert_email, a.summary_days, a.summary_hours, a.stamp_id'
            . ' FROM {alerts} AS a'
            . ' WHERE a.user_id = %1d AND EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( !$principal->isAdministrator() )
            $query .= ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %1d';
        $query .= ' WHERE ( f.folder_id = a.folder_id OR f.type_id = a.type_id ) AND p.is_archived = 0';

        if ( $includeSummary ) {
            $query .= ' AND ( a.alert_email > %2d AND f.stamp_id > COALESCE( a.stamp_id, 0 ) OR a.alert_email = %3d ) )';

            return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::NoEmail, System_Const::SummaryReportEmail );
        } else {
            $query .= ' AND ( a.alert_email = %2d AND f.stamp_id > COALESCE( a.stamp_id, 0 ) ) )';

            return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::ImmediateNotificationEmail );
        }
    }

    /**
    * Return public alerts for which emails should be sent.
    * @param $includeSummary If @c true, the summary notifications and reports are
    * included in addition to immediate notifications.
    * @return An array of associative arrays representing alerts.
    */
    public function getPublicAlertsToEmail( $includeSummary )
    {
        $query = 'SELECT a.alert_id, a.folder_id, a.type_id, a.view_id, a.alert_email, a.summary_days, a.summary_hours, a.stamp_id'
            . ' FROM {alerts} AS a'
            . ' WHERE a.user_id IS NULL AND EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id'
            . ' WHERE ( f.folder_id = a.folder_id OR f.type_id = a.type_id ) AND p.is_archived = 0';

        if ( $includeSummary ) {
            $query .= ' AND ( a.alert_email > %1d AND f.stamp_id > COALESCE( a.stamp_id, 0 ) OR a.alert_email = %2d ) )';

            return $this->connection->queryTable( $query, System_Const::NoEmail, System_Const::SummaryReportEmail );
        } else {
            $query .= ' AND ( a.alert_email = %1d AND f.stamp_id > COALESCE( a.stamp_id, 0 ) ) )';

            return $this->connection->queryTable( $query, System_Const::ImmediateNotificationEmail );
        }
    }

    /**
    * Return users for which emails related to a public alert should be sent.
    * @param $alert The public alert to get recipients.
    * @return An array of associative arrays representing users.
    */
    public function getAlertRecipients( $alert )
    {
        $folderId = $alert[ 'folder_id' ];
        $typeId = $alert[ 'type_id' ];
        $alertEmail = $alert[ 'alert_email' ];
        $stampId = $alert[ 'stamp_id' ];

        $query = 'SELECT u.user_id, u.user_name, u.user_access'
            . ' FROM {users} AS u'
            . ' JOIN {preferences} AS p ON p.user_id = u.user_id AND p.pref_key = %1s'
            . ' WHERE u.user_access > %2d AND EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( $folderId )
            $query .= ' WHERE f.folder_id = %4d';
        else
            $query .= ' WHERE f.type_id = %5d';
        $query .= ' AND ( u.user_access = %3d OR EXISTS ( SELECT r.project_id FROM {effective_rights} AS r WHERE r.project_id = f.project_id AND r.user_id = u.user_id ) )';
        if ( $alertEmail != System_Const::SummaryReportEmail && $stampId > 0 )
            $query .= ' AND f.stamp_id > %6d';
        $query .= ' AND p.is_archived = 0 )';

        return $this->connection->queryTable( $query, 'email', System_Const::NoAccess, System_Const::AdministratorAccess, $folderId, $typeId, $stampId );
    }

    /**
    * Update the stamp of last sent email for given alert.
    * @param $alert The alert to update.
    */
    public function updateAlertStamp( $alert )
    {
        $alertId = $alert[ 'alert_id' ];
        $folderId = $alert[ 'folder_id' ];

        if ( $folderId != null ) {
            $query = 'UPDATE {alerts}'
                . ' SET stamp_id = ( SELECT f.stamp_id FROM {folders} AS f WHERE f.folder_id = %d )'
                . ' WHERE alert_id = %d';

            $this->connection->execute( $query, $folderId, $alertId );
        } else {
            $typeId = $alert[ 'type_id' ];

            $query = 'UPDATE {alerts}'
                . ' SET stamp_id = ( SELECT MAX( f.stamp_id ) FROM {folders} AS f WHERE f.type_id = %d )'
                . ' WHERE alert_id = %d';

            $this->connection->execute( $query, $typeId, $alertId );
        }
    }
}
