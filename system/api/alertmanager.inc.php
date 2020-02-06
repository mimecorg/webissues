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
    /** Include daily reports. */
    const WithDaily = 4;
    /** Include weekly reports. */
    const WithWeekly = 8;
    /*@}*/

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Return all public and personal alerts for current user.
    * @return An array of associative arrays representing alerts.
    */
    public function getAlerts()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, a.type_id, a.view_id, a.project_id, a.folder_id,'
            . ' t.type_name, v.view_name, p.project_name, f.folder_name, ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public, v.view_def'
            . ' FROM ' . $this->generateJoins() . ' WHERE ( a.user_id = %1d OR a.user_id IS NULL ) AND a.alert_type = %3d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::Alert );
    }

    /**
    * Return all public alerts.
    * @return An array of associative arrays representing alerts.
    */
    public function getPublicAlerts()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, t.type_name, v.view_name, p.project_name, f.folder_name'
            . ' FROM ' . $this->generateJoins() . ' WHERE a.user_id IS NULL AND a.alert_type = %3d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::Alert );
    }

    /**
    * Return all personal alerts for current user.
    * @return An array of associative arrays representing alerts.
    */
    public function getPersonalAlerts()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, t.type_name, v.view_name, p.project_name, f.folder_name'
            . ' FROM ' . $this->generateJoins() . ' WHERE a.user_id = %1d AND a.alert_type = %3d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::Alert );
    }

    /**
    * Return all public reports.
    * @return An array of associative arrays representing reports.
    */
    public function getPublicReports()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, t.type_name, v.view_name, p.project_name, f.folder_name, a.alert_type, a.alert_frequency'
            . ' FROM ' . $this->generateJoins() . ' WHERE a.user_id IS NULL AND a.alert_type <> %3d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::Alert );
    }

    /**
    * Return all personal reports for current user.
    * @return An array of associative arrays representing reports.
    */
    public function getPersonalReports()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, t.type_name, v.view_name, p.project_name, f.folder_name, a.alert_type, a.alert_frequency'
            . ' FROM ' . $this->generateJoins() . ' WHERE a.user_id = %1d AND a.alert_type <> %3d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::AdministratorAccess, System_Const::Alert );
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

        $query = 'SELECT a.alert_id, t.type_name, v.view_name, p.project_name, f.folder_name,'
            . ' ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
            . ' FROM ' . $this->generateJoins() . ' WHERE ( a.user_id = %1d OR a.user_id IS NULL ) AND a.alert_id = %3d AND a.alert_type = %4d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        if ( !( $alert = $this->connection->queryRow( $query, $principal->getUserId(), System_Const::AdministratorAccess, $alertId, System_Const::Alert ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownAlert );

        if ( ( $flags & self::AllowEdit ) && !$principal->isAdministrator() && $alert[ 'is_public' ] )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $alert;
    }

    /**
    * Get the report with given identifier.
    * @param $reportId Identifier of the report.
    * @param $flags If AllowEdit is passed an error is thrown if the user
    * does not have permission to edit the report.
    * @return Array representing the report.
    */
    public function getReport( $reportId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, t.type_name, v.view_name, p.project_name, f.folder_name,'
            . ' ( CASE WHEN a.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public, a.alert_type, a.alert_frequency'
            . ' FROM ' . $this->generateJoins() . ' WHERE ( a.user_id = %1d OR a.user_id IS NULL ) AND a.alert_id = %3d AND a.alert_type <> %4d AND ' . $this->generateConditions()
            . ' ORDER BY t.type_name, v.view_name, p.project_name, f.folder_name';

        if ( !( $report = $this->connection->queryRow( $query, $principal->getUserId(), System_Const::AdministratorAccess, $reportId, System_Const::Alert ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownAlert );

        if ( ( $flags & self::AllowEdit ) && !$principal->isAdministrator() && $report[ 'is_public' ] )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $report;
    }

    private function generateJoins()
    {
        $principal = System_Api_Principal::getCurrent();

        $joins = '{alerts} AS a'
            . ' JOIN {issue_types} AS t ON t.type_id = a.type_id'
            . ' LEFT OUTER JOIN {views} AS v ON v.view_id = a.view_id'
            . ' LEFT OUTER JOIN {folders} AS f ON f.folder_id = a.folder_id'
            . ' LEFT OUTER JOIN {projects} AS p ON p.project_id = COALESCE( a.project_id, f.project_id )';

        return $joins;
    }

    private function generateConditions()
    {
        $principal = System_Api_Principal::getCurrent();

        if ( !$principal->isAdministrator() ) {
            return '( EXISTS( SELECT * FROM {rights}'
                . ' WHERE user_id = %1d AND project_access = %2d AND project_id IN ( SELECT project_id FROM {projects} WHERE is_archived = 0 ) )'
                . ' OR t.type_id IN ( SELECT f2.type_id FROM {folders} AS f2'
                . ' JOIN {projects} AS p2 ON p2.project_id = f2.project_id'
                . ' WHERE p2.is_archived = 0 AND ( p2.project_id IN ( SELECT project_id FROM {rights} WHERE user_id = %1d ) OR p2.is_public = 1 ) ) )'
                . ' AND ( p.is_archived = 0 AND ( p.project_id IN ( SELECT project_id FROM {rights} WHERE user_id = %1d ) OR p.is_public = 1 ) OR p.project_id IS NULL )';
        } else {
            return '( p.is_archived = 0 OR p.project_id IS NULL )';
        }
    }

    /**
    * Create a new alert. An error is thrown if such alert already exists.
    * @param $type Type associated with the alert.
    * @param $view Optional view associated with the alert.
    * @param $project Optional project for which the alert is created.
    * @param $folder Optional folder for which the alert is created.
    * @param $alertType Type of the alert.
    * @param $alertFrequency Frequency of the alert.
    * @param $flags If IsPublic is passed, a public alert is created.
    * @return The identifier of the new alert.
    */
    public function addAlert( $type, $view, $project, $folder, $alertType, $alertFrequency, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];
        $viewId = ( $view != null ) ? $view[ 'view_id' ] : null;
        $projectId = ( $project != null ) ? $project[ 'project_id' ] : null;
        $folderId = ( $folder != null ) ? $folder[ 'folder_id' ] : null;
        $stampId = ( $folder != null ) ? $folder[ 'stamp_id' ] : null;
        $userId = ( $flags & self::IsPublic ) ? null : $principal->getUserId();

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'alerts' );

        try {
            if ( $flags & self::IsPublic )
                $query = 'SELECT alert_id FROM {alerts} WHERE user_id IS NULL';
            else
                $query = 'SELECT alert_id FROM {alerts} WHERE ( user_id = %1d OR user_id IS NULL )';
            $query .= ' AND type_id = %2d AND view_id = %3d? AND project_id = %4d? AND folder_id = %5d? AND alert_type = %6d';

            if ( $this->connection->queryScalar( $query, $userId, $typeId, $viewId, $projectId, $folderId, $alertType ) !== false )
                throw new System_Api_Error( System_Api_Error::AlertAlreadyExists );

            if ( $stampId == null ) {
                $query = 'SELECT MAX( stamp_id ) FROM {folders} WHERE type_id = %d';
                $stampId = $this->connection->queryScalar( $query, $typeId );
            }

            $query = 'INSERT INTO {alerts} ( user_id, type_id, view_id, project_id, folder_id, alert_type, alert_frequency, stamp_id ) VALUES ( %d?, %d, %d?, %d?, %d?, %d, %d, %d? )';
            $this->connection->execute( $query, $userId, $typeId, $viewId, $projectId, $folderId, $alertType, $alertFrequency, $stampId );
            $alertId = $this->connection->getInsertId( 'alerts', 'alert_id' );

            if ( $flags & self::IsPublic ) {
                $query = 'DELETE FROM {alerts} WHERE user_id IS NOT NULL AND type_id = %d AND view_id = %d? AND project_id = %d? AND folder_id = %d? AND alert_type = %d';
                $this->connection->execute( $query, $typeId, $viewId, $projectId, $folderId, $alertType );
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
    * @param $alertType Type of the alert.
    * @param $alertFrequency Frequency of the alert.
    * @return @c true if the alert was modified.
    */
    public function modifyAlert( $alert, $alertType, $alertFrequency )
    {
        $alertId = $alert[ 'alert_id' ];
        $oldType = $alert[ 'alert_type' ];
        $oldFrequency = $alert[ 'alert_frequency' ];

        if ( $alertType == $oldType && $alertFrequency == $oldFrequency )
            return false;

        $query = 'UPDATE {alerts} SET alert_type = %d, alert_frequency = %d WHERE alert_id = %d';
        $this->connection->execute( $query, $alertType, $alertFrequency, $alertId );

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

    public function getIssueTypeFromAlert( $alert )
    {
        $type[ 'type_id' ] = $alert[ 'type_id' ];
        $type[ 'type_name' ] = $alert[ 'type_name' ];
        return $type;
    }

    public function getViewFromAlert( $alert )
    {
        if ( $alert[ 'view_id' ] != null ) {
            $view[ 'view_id' ] = $alert[ 'view_id' ];
            $view[ 'view_name' ] = $alert[ 'view_name' ];
            $view[ 'view_def' ] = $alert[ 'view_def' ];
            return $view;
        } else {
            return null;
        }
    }

    public function getProjectFromAlert( $alert )
    {
        if ( $alert[ 'project_id' ] != null ) {
            $project[ 'project_id' ] = $alert[ 'project_id' ];
            $project[ 'project_name' ] = $alert[ 'project_name' ];
        } else {
            return null;
        }
    }

    public function getFolderFromAlert( $alert )
    {
        if ( $alert[ 'folder_id' ] != null ) {
            $folder[ 'folder_id' ] = $alert[ 'folder_id' ];
            $folder[ 'folder_name' ] = $alert[ 'folder_name' ];
            $folder[ 'type_id' ] = $alert[ 'type_id' ];
            return $folder;
        } else {
            return null;
        }
    }

    /**
    * Return alerts for which emails should be sent.
    * @param $flags If WithDaily is passed, daily reports are included.
    * If WithWeekly is passed, weekly reports are included.
    * @return An array of associative arrays representing alerts.
    */
    public function getAlertsToEmail( $flags )
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT a.alert_id, a.type_id, a.view_id, a.project_id, a.folder_id, a.alert_type, a.alert_frequency, a.stamp_id'
            . ' FROM {alerts} AS a WHERE ' . $this->generateAlertsToEmailConditions( $flags, $principal );

        return $this->connection->queryTable( $query, $principal->getUserId(), System_Const::Alert, System_Const::Daily, System_Const::IssueReport );
    }

    /**
    * Return public alerts for which emails should be sent.
    * @param $flags If WithDaily is passed, daily reports are included.
    * If WithWeekly is passed, weekly reports are included.
    * @return An array of associative arrays representing alerts.
    */
    public function getPublicAlertsToEmail( $flags )
    {
        $query = 'SELECT a.alert_id, a.type_id, a.view_id, a.project_id, a.folder_id, a.alert_type, a.alert_frequency, a.stamp_id'
            . ' FROM {alerts} AS a WHERE ' . $this->generateAlertsToEmailConditions( $flags, null );

        return $this->connection->queryTable( $query, null, System_Const::Alert, System_Const::Daily, System_Const::IssueReport );
    }

    private function generateAlertsToEmailConditions( $flags, $principal )
    {
        if ( $principal != null )
            $conditions = 'a.user_id = %1d';
        else
            $conditions = 'a.user_id IS NULL';

        if ( $flags & self::WithDaily ) {
            if ( !( $flags & self::WithWeekly ) )
                $conditions .= ' AND a.alert_frequency = %3d';
        } else {
            $conditions .= ' AND a.alert_type = %2d';
        }

        $conditions .= ' AND EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id'
            . ' WHERE f.type_id = a.type_id'
            . ' AND ( f.folder_id = a.folder_id OR f.project_id = a.project_id OR a.folder_id IS NULL AND a.project_id IS NULL )'
            . ' AND p.is_archived = 0';

        if ( $principal != null && !$principal->isAdministrator() )
            $conditions .= ' AND ( p.project_id IN ( SELECT project_id FROM {rights} WHERE user_id = %1d ) OR p.is_public = 1 )';

        if ( $flags & self::WithDaily )
            $conditions .= ' AND ( f.stamp_id > COALESCE( a.stamp_id, 0 ) OR a.alert_type = %4d ) )';
        else
            $conditions .= ' AND f.stamp_id > COALESCE( a.stamp_id, 0 ) )';

        return $conditions;
    }

    /**
    * Return users for which emails related to a public alert should be sent.
    * @param $alert The public alert to get recipients.
    * @return An array of associative arrays representing users.
    */
    public function getAlertRecipients( $alert )
    {
        $typeId = $alert[ 'type_id' ];
        $projectId = $alert[ 'project_id' ];
        $folderId = $alert[ 'folder_id' ];
        $alertType = $alert[ 'alert_type' ];
        $stampId = $alert[ 'stamp_id' ];

        $query = 'SELECT u.user_id, u.user_name, u.user_access, u.user_email, u.user_language'
            . ' FROM {users} AS u'
            . ' WHERE u.user_access > %1d AND EXISTS ( SELECT f.folder_id FROM {folders} AS f'
            . ' JOIN {projects} AS p ON p.project_id = f.project_id';
        if ( $folderId != null ) {
            $query .= ' WHERE f.folder_id = %5d';
        } else {
            $query .= ' WHERE f.type_id = %3d';
            if ( $projectId != null )
                $query .= ' AND f.project_id = %4d';
        }
        $query .= ' AND p.is_archived = 0'
            . ' AND ( u.user_access = %2d OR p.is_public = 1 OR EXISTS ( SELECT r.project_id FROM {rights} AS r WHERE r.project_id = p.project_id AND r.user_id = u.user_id ) )';
        if ( $alertType != System_Const::IssueReport && $stampId != null )
            $query .= ' AND f.stamp_id > %6d';
        $query .= ' )';

        return $this->connection->queryTable( $query, System_Const::NoAccess, System_Const::AdministratorAccess, $typeId, $projectId, $folderId, $stampId );
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
