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

class Admin_Setup_Updater extends System_Web_Base
{
    private $connection = null;

    public function __construct( $connection )
    {
        parent::__construct();

        $this->connection = $connection;
    }

    public function updateDatabase( $version )
    {
        if ( version_compare( $version, '1.0.002' ) < 0 ) {
            $settings = array(
                'folder_page_size'      => 10,
                'history_page_size'     => 20
            );

            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
            foreach ( $settings as $key => $value )
                $this->connection->execute( $query, $key, $value );
        }

        if ( version_compare( $version, '1.0.003' ) < 0 ) {
            $fields = array(
                'request_id'        => 'SERIAL',
                'user_login'        => 'VARCHAR length=40',
                'user_name'         => 'VARCHAR length=40',
                'user_email'        => 'VARCHAR length=40',
                'user_passwd'       => 'VARCHAR length=255 ascii=1',
                'request_key'       => 'CHAR length=8 ascii=1',
                'created_time'      => 'INTEGER',
                'is_active'         => 'INTEGER size="tiny"',
                'is_sent'           => 'INTEGER size="tiny"',
                'pk'                => 'PRIMARY columns={"request_id"}'
            );

            $generator = $this->connection->getSchemaGenerator();

            $generator->createTable( 'register_requests', $fields );
            $generator->updateReferences();

            $settings = array(
                'register_max_lifetime' => 86400
            );

            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
            foreach ( $settings as $key => $value )
                $this->connection->execute( $query, $key, $value );
        }

        if ( version_compare( $version, '1.0.004' ) < 0 ) {
            $settings = array(
                'history_order'         => 'asc',
                'history_filter'        => 1
            );

            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
            foreach ( $settings as $key => $value )
                $this->connection->execute( $query, $key, $value );
        }

        if ( version_compare( $version, '1.1.001' ) < 0 ) {
            $newTables = array(
                'fc_temp'     => array(
                    'issue_id'          => 'INTEGER',
                    'comment_id'        => 'INTEGER',
                    'stamp_id'          => 'INTEGER',
                    'pk'                => 'PRIMARY columns={"issue_id"}'
                ),
                'issue_descriptions' => array(
                    'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                    'descr_text'        => 'TEXT size="long"',
                    'descr_format'      => 'INTEGER size="tiny" default=0',
                    'pk'                => 'PRIMARY columns={"issue_id"}'
                ),
                'project_descriptions' => array(
                    'project_id'        => 'INTEGER ref-table="projects" ref-column="project_id" on-delete="cascade"',
                    'descr_text'        => 'TEXT size="long"',
                    'descr_format'      => 'INTEGER size="tiny" default=0',
                    'pk'                => 'PRIMARY columns={"project_id"}'
                )
            );

            $newFields = array(
                'comments' => array(
                    'comment_format'    => 'INTEGER size="tiny" default=0'
                ),
                'issues' => array(
                    'descr_id'          => 'INTEGER null=1',
                    'descr_stub_id'     => 'INTEGER null=1'
                ),
                'projects' => array(
                    'stamp_id'          => 'INTEGER null=1',
                    'descr_id'          => 'INTEGER null=1',
                    'descr_stub_id'     => 'INTEGER null=1'
                )
            );

            $generator = $this->connection->getSchemaGenerator();

            foreach ( $newTables as $tableName => $fields )
                $generator->createTable( $tableName, $fields );

            foreach ( $newFields as $tableName => $fields )
                $generator->addFields( $tableName, $fields );

            $generator->updateReferences();

            $query = 'INSERT INTO {fc_temp} ( issue_id, comment_id, stamp_id )'
                . ' SELECT fc.issue_id, fc.comment_id, ch.stamp_id'
                . ' FROM ( SELECT issue_id, MIN( change_id ) AS comment_id FROM {changes} WHERE change_type = %d GROUP BY issue_id ) AS fc'
                . ' INNER JOIN {changes} AS ch ON ch.change_id = fc.comment_id'
                . ' INNER JOIN {stamps} AS sc ON sc.stamp_id = fc.comment_id'
                . ' INNER JOIN {stamps} AS si ON si.stamp_id = fc.issue_id'
                . ' WHERE sc.user_id = si.user_id AND ( sc.stamp_time - si.stamp_time ) <= %d';
            $this->connection->execute( $query, System_Const::CommentAdded, 900 );

            $query = 'INSERT INTO {issue_descriptions} ( issue_id, descr_text, descr_format )'
                . ' SELECT fc.issue_id, c.comment_text AS descr_text, %d AS descr_format'
                . ' FROM {fc_temp} AS fc'
                . ' INNER JOIN {comments} AS c ON c.comment_id = fc.comment_id';
            $this->connection->execute( $query, System_Const::PlainText );

            $query = 'UPDATE {issues}'
                . ' SET descr_id = ( SELECT stamp_id FROM {fc_temp} WHERE {fc_temp}.issue_id = {issues}.issue_id )'
                . ' WHERE issue_id IN ( SELECT issue_id FROM {fc_temp} )';
            $this->connection->execute( $query );

            $query = 'DELETE FROM {changes}'
                . ' WHERE change_id IN ( SELECT comment_id FROM {fc_temp} )';
            $this->connection->execute( $query );

            $query = 'DROP TABLE {fc_temp}';
            $this->connection->execute( $query );

            $settings = array(
                'default_format'        => 1
            );

            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
            foreach ( $settings as $key => $value )
                $this->connection->execute( $query, $key, $value );
        }

        if ( version_compare( $version, '1.1.002' ) < 0 ) {
            $newTables = array(
                'subscriptions' => array(
                    'subscription_id'   => 'SERIAL',
                    'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                    'user_id'           => 'INTEGER null=1 ref-table="users" ref-column="user_id"',
                    'user_email'        => 'VARCHAR length=255 null=1',
                    'stamp_id'          => 'INTEGER',
                    'pk'                => 'PRIMARY columns={"subscription_id"}',
                    'issue_idx'         => 'INDEX columns={"issue_id","user_id"}'
                )
            );

            $newFields = array(
                'changes' => array(
                    'subscription_id'   => 'INTEGER null=1'
                ),
                'issue_states' => array(
                    'subscription_id'   => 'INTEGER null=1'
                )
            );

            $generator = $this->connection->getSchemaGenerator();

            foreach ( $newTables as $tableName => $fields )
                $generator->createTable( $tableName, $fields );

            foreach ( $newFields as $tableName => $fields )
                $generator->addFields( $tableName, $fields );

            $generator->updateReferences();
        }

        if ( version_compare( $version, '1.1.003' ) < 0 ) {
            $modifiedFields = array(
                'user_id'           => 'INTEGER null=1 ref-table="users" ref-column="user_id"',
                'folder_id'         => 'INTEGER null=1 ref-table="folders" ref-column="folder_id" on-delete="cascade"'
            );

            $newFields = array(
                'type_id'           => 'INTEGER null=1 ref-table="issue_types" ref-column="type_id" on-delete="cascade" trigger=1',
                'summary_days'      => 'VARCHAR length=255 null=1',
                'summary_hours'     => 'VARCHAR length=255 null=1',
                'type_idx'          => 'INDEX columns={"type_id"}'
            );

            $modifiedIndexes = array(
                'alert_idx'         => 'INDEX columns={"user_id","folder_id","type_id","view_id"} unique=1'
            );

            $generator = $this->connection->getSchemaGenerator();

            $generator->modifyFieldsNull( 'alerts', $modifiedFields );
            $generator->addFields( 'alerts', $newFields );
            $generator->modifyIndexColumns( 'alerts', $modifiedIndexes );

            $generator->updateReferences();

            $query = 'UPDATE {alerts} SET summary_days = ( SELECT pref_value FROM {preferences} AS p WHERE p.user_id = {alerts}.user_id AND p.pref_key = %s ),'
                . ' summary_hours = ( SELECT pref_value FROM {preferences} AS p WHERE p.user_id = {alerts}.user_id AND p.pref_key = %s )'
                . ' WHERE alert_email >= %d';
            $this->connection->execute( $query, 'summary_days', 'summary_hours', System_Const::SummaryNotificationEmail );

            $query = 'DELETE FROM {preferences} WHERE pref_key IN ( %%s )';
            $this->connection->execute( $query, array( 'summary_days', 'summary_hours' ) );
        }

        if ( version_compare( $version, '1.1.004' ) < 0 ) {
            $newFields = array(
                'is_public'         => 'INTEGER size="tiny" default=0'
            );

            $generator = $this->connection->getSchemaGenerator();

            $generator->addFields( 'projects', $newFields );

            $query = 'SELECT p.project_id, u.user_id, COALESCE( r.project_access, 1 ) AS project_access'
                . ' FROM {projects} AS p'
                . ' CROSS JOIN {users} AS u'
                . ' LEFT OUTER JOIN {rights} AS r ON r.project_id = p.project_id AND r.user_id = u.user_id'
                . ' WHERE r.project_access IS NOT NULL OR p.is_public = 1';

            $generator->createView( 'effective_rights', $query );

            $generator->updateReferences();
        }

        if ( version_compare( $version, '1.1.005' ) < 0 ) {
            $settings = array(
                'project_page_size'     => 10
            );

            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
            foreach ( $settings as $key => $value )
                $this->connection->execute( $query, $key, $value );
        }

        if ( version_compare( $version, '1.1.006' ) < 0 ) {
            $newFields = array(
                'is_archived'       => 'INTEGER size="tiny" default=0',
            );

            $generator = $this->connection->getSchemaGenerator();

            $generator->addFields( 'projects', $newFields );

            $generator->updateReferences();
        }

        if ( version_compare( $version, '1.1.007' ) < 0 ) {
            $settings = array(
                'project_page_mobile'   => 5,
                'folder_page_mobile'    => 10,
                'history_page_mobile'   => 10,
            );

            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
            foreach ( $settings as $key => $value )
                $this->connection->execute( $query, $key, $value );
        }

        $query = 'DELETE FROM {sessions}';
        $this->connection->execute( $query );

        $query = 'UPDATE {server} SET db_version = %s';
        $this->connection->execute( $query, WI_DATABASE_VERSION );
    }
}
