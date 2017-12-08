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

class Admin_Setup_Installer extends System_Web_Base
{
    private $connection = null;
    private $generator = null;

    public function __construct( $connection )
    {
        parent::__construct();

        $this->connection = $connection;
        $this->generator = $connection->getSchemaGenerator();
    }

    public function installSchema()
    {
        $schema = array(
            'alerts' => array(
                'alert_id'          => 'SERIAL',
                'user_id'           => 'INTEGER null=1 ref-table="users" ref-column="user_id"',
                'folder_id'         => 'INTEGER null=1 ref-table="folders" ref-column="folder_id" on-delete="cascade"',
                'type_id'           => 'INTEGER null=1 ref-table="issue_types" ref-column="type_id" on-delete="cascade" trigger=1',
                'view_id'           => 'INTEGER null=1 ref-table="views" ref-column="view_id" on-delete="cascade"',
                'alert_email'       => 'INTEGER size="tiny"',
                'stamp_id'          => 'INTEGER null=1',
                'summary_days'      => 'VARCHAR length=255 null=1',
                'summary_hours'     => 'VARCHAR length=255 null=1',
                'pk'                => 'PRIMARY columns={"alert_id"}',
                'alert_idx'         => 'INDEX columns={"user_id","folder_id","type_id","view_id"} unique=1',
                'folder_idx'        => 'INDEX columns={"folder_id"}',
                'type_idx'          => 'INDEX columns={"type_id"}',
                'view_idx'          => 'INDEX columns={"view_id"}'
            ),
            'attr_types' => array(
                'attr_id'           => 'SERIAL',
                'type_id'           => 'INTEGER ref-table="issue_types" ref-column="type_id" on-delete="cascade"',
                'attr_name'         => 'VARCHAR length=40',
                'attr_def'          => 'TEXT size="long"',
                'pk'                => 'PRIMARY columns={"attr_id"}',
                'type_idx'          => 'INDEX columns={"type_id"}',
                'name_idx'          => 'INDEX columns={"attr_name"}'
            ),
            'attr_values' => array(
                'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                'attr_id'           => 'INTEGER ref-table="attr_types" ref-column="attr_id" on-delete="cascade"',
                'attr_value'        => 'VARCHAR length=255',
                'pk'                => 'PRIMARY columns={"issue_id","attr_id"}',
                'attr_idx'          => 'INDEX columns={"attr_id"}'
            ),
            'change_stubs' => array(
                'stub_id'           => 'INTEGER ref-table="stamps" ref-column="stamp_id"',
                'change_id'         => 'INTEGER',
                'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                'pk'                => 'PRIMARY columns={"stub_id"}',
                'issue_idx'         => 'INDEX columns={"issue_id"}'
            ),
            'changes' => array(
                'change_id'         => 'INTEGER ref-table="stamps" ref-column="stamp_id"',
                'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                'change_type'       => 'INTEGER size="tiny"',
                'stamp_id'          => 'INTEGER',
                'attr_id'           => 'INTEGER null=1 ref-table="attr_types" ref-column="attr_id" on-delete="cascade"',
                'value_old'         => 'VARCHAR length=255 null=1',
                'value_new'         => 'VARCHAR length=255 null=1',
                'from_folder_id'    => 'INTEGER null=1 ref-table="folders" ref-column="folder_id" on-delete="set-null" trigger=1',
                'to_folder_id'      => 'INTEGER null=1 ref-table="folders" ref-column="folder_id" on-delete="set-null" trigger=1',
                'subscription_id'   => 'INTEGER null=1',
                'pk'                => 'PRIMARY columns={"change_id"}',
                'issue_idx'         => 'INDEX columns={"issue_id","change_type"}',
                'stamp_idx'         => 'INDEX columns={"stamp_id"}',
                'attr_idx'          => 'INDEX columns={"attr_id"}',
                'from_folder_idx'   => 'INDEX columns={"from_folder_id"}',
                'to_folder_idx'     => 'INDEX columns={"to_folder_id"}'
            ),
            'comments' => array(
                'comment_id'        => 'INTEGER ref-table="changes" ref-column="change_id" on-delete="cascade"',
                'comment_text'      => 'TEXT size="long"',
                'comment_format'    => 'INTEGER size="tiny" default=0',
                'pk'                => 'PRIMARY columns={"comment_id"}'
            ),
            'files' => array(
                'file_id'           => 'INTEGER ref-table="changes" ref-column="change_id" on-delete="cascade"',
                'file_name'         => 'VARCHAR length=80',
                'file_size'         => 'INTEGER',
                'file_data'         => 'BLOB size="long" null=1',
                'file_descr'        => 'VARCHAR length=255',
                'file_storage'      => 'INTEGER size="tiny"',
                'pk'                => 'PRIMARY columns={"file_id"}'
            ),
            'folders' => array(
                'folder_id'         => 'SERIAL',
                'project_id'        => 'INTEGER ref-table="projects" ref-column="project_id" on-delete="cascade"',
                'type_id'           => 'INTEGER ref-table="issue_types" ref-column="type_id" on-delete="cascade" trigger=1',
                'folder_name'       => 'VARCHAR length=40',
                'stamp_id'          => 'INTEGER null=1',
                'pk'                => 'PRIMARY columns={"folder_id"}',
                'project_idx'       => 'INDEX columns={"project_id"}',
                'type_idx'          => 'INDEX columns={"type_id"}',
                'name_idx'          => 'INDEX columns={"folder_name"}'
            ),
            'issue_descriptions' => array(
                'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                'descr_text'        => 'TEXT size="long"',
                'descr_format'      => 'INTEGER size="tiny" default=0',
                'pk'                => 'PRIMARY columns={"issue_id"}'
            ),
            'issue_states' => array(
                'state_id'          => 'SERIAL',
                'user_id'           => 'INTEGER ref-table="users" ref-column="user_id"',
                'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                'read_id'           => 'INTEGER null=1',
                'subscription_id'   => 'INTEGER null=1',
                'pk'                => 'PRIMARY columns={"state_id"}',
                'state_idx'         => 'INDEX columns={"user_id","issue_id"} unique=1',
                'issue_idx'         => 'INDEX columns={"issue_id"}'
            ),
            'issue_stubs' => array(
                'stub_id'           => 'INTEGER ref-table="stamps" ref-column="stamp_id"',
                'prev_id'           => 'INTEGER',
                'issue_id'          => 'INTEGER',
                'folder_id'         => 'INTEGER ref-table="folders" ref-column="folder_id" on-delete="cascade"',
                'pk'                => 'PRIMARY columns={"stub_id"}',
                'folder_idx'        => 'INDEX columns={"folder_id"}'
            ),
            'issue_types' => array(
                'type_id'           => 'SERIAL',
                'type_name'         => 'VARCHAR length=40',
                'pk'                => 'PRIMARY columns={"type_id"}',
                'name_idx'          => 'INDEX columns={"type_name"}'
            ),
            'issues' => array(
                'issue_id'          => 'INTEGER ref-table="stamps" ref-column="stamp_id"',
                'folder_id'         => 'INTEGER ref-table="folders" ref-column="folder_id" on-delete="cascade"',
                'issue_name'        => 'VARCHAR length=255',
                'stamp_id'          => 'INTEGER ref-table="stamps" ref-column="stamp_id"',
                'stub_id'           => 'INTEGER null=1',
                'descr_id'          => 'INTEGER null=1',
                'descr_stub_id'     => 'INTEGER null=1',
                'pk'                => 'PRIMARY columns={"issue_id"}',
                'folder_idx'        => 'INDEX columns={"folder_id"}',
                'stamp_idx'         => 'INDEX columns={"stamp_id"}'
            ),
            'log_events' => array(
                'event_id'          => 'SERIAL',
                'event_type'        => 'VARCHAR length=16 ascii=1',
                'event_severity'    => 'INTEGER size="tiny"',
                'event_message'     => 'TEXT size="long"',
                'event_time'        => 'INTEGER',
                'user_id'           => 'INTEGER null=1 ref-table="users" ref-column="user_id"',
                'host_name'         => 'VARCHAR length=40 ascii=1',
                'pk'                => 'PRIMARY columns={"event_id"}',
                'type_idx'          => 'INDEX columns={"event_type"}',
                'user_idx'          => 'INDEX columns={"user_id"}'
            ),
            'preferences' => array(
                'user_id'           => 'INTEGER ref-table="users" ref-column="user_id"',
                'pref_key'          => 'VARCHAR length=40',
                'pref_value'        => 'TEXT size="long"',
                'pk'                => 'PRIMARY columns={"user_id","pref_key"}'
            ),
            'project_descriptions' => array(
                'project_id'        => 'INTEGER ref-table="projects" ref-column="project_id" on-delete="cascade"',
                'descr_text'        => 'TEXT size="long"',
                'descr_format'      => 'INTEGER size="tiny" default=0',
                'pk'                => 'PRIMARY columns={"project_id"}'
            ),
            'projects' => array(
                'project_id'        => 'SERIAL',
                'project_name'      => 'VARCHAR length=40',
                'stamp_id'          => 'INTEGER null=1',
                'descr_id'          => 'INTEGER null=1',
                'descr_stub_id'     => 'INTEGER null=1',
                'is_public'         => 'INTEGER size="tiny" default=0',
                'is_archived'       => 'INTEGER size="tiny" default=0',
                'pk'                => 'PRIMARY columns={"project_id"}',
                'name_idx'          => 'INDEX columns={"project_name"}'
            ),
            'rights' => array(
                'project_id'        => 'INTEGER ref-table="projects" ref-column="project_id" on-delete="cascade"',
                'user_id'           => 'INTEGER ref-table="users" ref-column="user_id"',
                'project_access'    => 'INTEGER size="tiny"',
                'pk'                => 'PRIMARY columns={"project_id","user_id"}',
                'user_idx'          => 'INDEX columns={"user_id"}'
            ),
            'register_requests' => array(
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
            ),
            'server' => array(
                'server_name'       => 'VARCHAR length=40',
                'server_uuid'       => 'CHAR length=36 ascii=1',
                'db_version'        => 'VARCHAR length=20 ascii=1'
            ),
            'sessions' => array(
                'session_id'        => 'CHAR length=32 ascii=1',
                'user_id'           => 'INTEGER ref-table="users" ref-column="user_id"',
                'session_data'      => 'TEXT size="long"',
                'last_access'       => 'INTEGER',
                'pk'                => 'PRIMARY columns={"session_id"}',
                'user_idx'          => 'INDEX columns={"user_id"}',
                'access_idx'        => 'INDEX columns={"last_access"}'
            ),
            'settings' => array(
                'set_key'           => 'VARCHAR length=40',
                'set_value'         => 'TEXT size="long"',
                'pk'                => 'PRIMARY columns={"set_key"}'
            ),
            'stamps' => array(
                'stamp_id'          => 'SERIAL',
                'user_id'           => 'INTEGER ref-table="users" ref-column="user_id"',
                'stamp_time'        => 'INTEGER',
                'pk'                => 'PRIMARY columns={"stamp_id"}',
                'user_idx'          => 'INDEX columns={"user_id"}'
            ),
            'subscriptions' => array(
                'subscription_id'   => 'SERIAL',
                'issue_id'          => 'INTEGER ref-table="issues" ref-column="issue_id" on-delete="cascade"',
                'user_id'           => 'INTEGER null=1 ref-table="users" ref-column="user_id"',
                'user_email'        => 'VARCHAR length=255 null=1',
                'stamp_id'          => 'INTEGER',
                'pk'                => 'PRIMARY columns={"subscription_id"}',
                'issue_idx'         => 'INDEX columns={"issue_id","user_id"}'
            ),
            'users' => array(
                'user_id'           => 'SERIAL',
                'user_login'        => 'VARCHAR length=40',
                'user_name'         => 'VARCHAR length=40',
                'user_passwd'       => 'VARCHAR length=255 ascii=1',
                'user_access'       => 'INTEGER size="tiny"',
                'passwd_temp'       => 'INTEGER size="tiny"',
                'pk'                => 'PRIMARY columns={"user_id"}',
                'login_idx'         => 'INDEX columns={"user_login"} unique=1',
                'name_idx'          => 'INDEX columns={"user_name"}'
            ),
            'view_settings' => array(
                'type_id'           => 'INTEGER ref-table="issue_types" ref-column="type_id" on-delete="cascade"',
                'set_key'           => 'VARCHAR length=40',
                'set_value'         => 'TEXT size="long"',
                'pk'                => 'PRIMARY columns={"type_id","set_key"}'
            ),
            'views' => array(
                'view_id'           => 'SERIAL',
                'type_id'           => 'INTEGER ref-table="issue_types" ref-column="type_id" on-delete="cascade"',
                'user_id'           => 'INTEGER null=1 ref-table="users" ref-column="user_id"',
                'view_name'         => 'VARCHAR length=40',
                'view_def'          => 'TEXT size="long"',
                'pk'                => 'PRIMARY columns={"view_id"}',
                'view_idx'          => 'INDEX columns={"type_id","user_id"}',
                'user_idx'          => 'INDEX columns={"user_id"}',
                'name_idx'          => 'INDEX columns={"view_name"}'
            )
        );

        foreach ( $schema as $tableName => $fields )
            $this->generator->createTable( $tableName, $fields );

        $this->generator->updateReferences();

        $query = 'SELECT p.project_id, u.user_id, COALESCE( r.project_access, 1 ) AS project_access'
            . ' FROM {projects} AS p'
            . ' CROSS JOIN {users} AS u'
            . ' LEFT OUTER JOIN {rights} AS r ON r.project_id = p.project_id AND r.user_id = u.user_id'
            . ' WHERE r.project_access IS NOT NULL OR p.is_public = 1';

        $this->generator->createView( 'effective_rights', $query );
    }

    public function installData( $serverName, $adminPassword )
    {
        $serverManager = new System_Api_ServerManager();
        $uuid = $serverManager->generateUuid();

        $query = 'INSERT INTO {server} ( server_name, server_uuid, db_version ) VALUES ( %s, %s, %s )';
        $this->connection->execute( $query, $serverName, $uuid, WI_DATABASE_VERSION );

        $passwordHash = new System_Core_PasswordHash();
        $hash = $passwordHash->hashPassword( $adminPassword );

        $query = 'INSERT INTO {users} ( user_login, user_name, user_passwd, user_access, passwd_temp ) VALUES ( %s, %s, %s, %d, 0 )';
        $this->connection->execute( $query, 'admin', $this->tr( 'Administrator' ), $hash, System_Const::AdministratorAccess );

        $language = $this->translator->getLanguage( System_Core_Translator::SystemLanguage );

        $settings = array(
            'language'              => $language,
            'comment_max_length'    => 10000,
            'default_format'        => 1,
            'project_page_size'     => 10,
            'folder_page_size'      => 10,
            'history_page_size'     => 20,
            'project_page_mobile'   => 5,
            'folder_page_mobile'    => 10,
            'history_page_mobile'   => 10,
            'history_order'         => 'asc',
            'history_filter'        => 1,
            'file_max_size'         => 1048576,
            'file_db_max_size'      => 4096,
            'session_max_lifetime'  => 7200,
            'log_max_lifetime'      => 604800,
            'register_max_lifetime' => 86400,
            'gc_divisor'            => 100
        );

        $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %s, %s )';
        foreach ( $settings as $key => $value )
            $this->connection->execute( $query, $key, $value );
    }

    public function installDefaultTypes()
    {
        $types = array(
            array(
                $this->tr( 'Forum' ),
                array(),
                array(),
                array()
            ),
            array(
                $this->tr( 'Bugs' ),
                array(
                    'assigned-to' => array(
                        $this->tr( 'Assigned To', 'bug' ),
                        'USER',
                        array(
                            'members' => 1
                        )
                    ),
                    'status' => array(
                        $this->tr( 'Status', 'bug' ),
                        'ENUM',
                        array(
                            'items' => array( $this->tr( 'Active', 'bug' ), $this->tr( 'Resolved', 'bug' ), $this->tr( 'Closed', 'bug' ) ),
                            'required' => 1,
                            'default' => $this->tr( 'Active', 'bug' )
                        )
                    ),
                    'reason' => array(
                        $this->tr( 'Reason' ),
                        'ENUM',
                        array(
                            'items' => array( $this->tr( 'Fixed' ), $this->tr( 'Obsolete' ), $this->tr( 'Duplicate' ),
                                $this->tr( 'As Designed' ), $this->tr( 'Unable To Reproduce' ), $this->tr( 'Test Failed' ) )
                        )
                    ),
                    'severity' => array(
                        $this->tr( 'Severity', 'bug' ),
                        'NUMERIC',
                        array(
                            'min-value' => '1',
                            'max-value' => '3',
                            'required' => 1,
                            'default' => '2'
                        )
                    ),
                    'version' => array(
                        $this->tr( 'Version' ),
                        'TEXT',
                        array()
                    )
                ),
                array( 'assigned-to', 'status', 'severity' ),
                array(
                    array(
                        $this->tr( 'Created By Me', 'bug' ),
                        array( 'assigned-to', 'status', 'severity' ),
                        array(
                            System_Api_Column::CreatedBy, 'EQ', '[Me]'
                        )
                    ),
                    array(
                        $this->tr( 'Active Bugs' ),
                        array( 'assigned-to', 'severity' ),
                        array(
                            'status', 'EQ', $this->tr( 'Active', 'bug' )
                        )
                    ),
                    array(
                        $this->tr( 'My Active Bugs' ),
                        array( System_Api_Column::CreatedDate, System_Api_Column::CreatedBy, 'severity' ),
                        array(
                            'assigned-to', 'EQ', '[Me]',
                            'status', 'EQ', $this->tr( 'Active', 'bug' )
                        )
                    ),
                    array(
                        $this->tr( 'Unassigned Bugs' ),
                        array( System_Api_Column::CreatedDate, System_Api_Column::CreatedBy, 'severity' ),
                        array(
                            'assigned-to', 'EQ', '',
                            'status', 'EQ', $this->tr( 'Active', 'bug' )
                        )
                    ),
                    array(
                        $this->tr( 'Resolved Bugs' ),
                        array( 'assigned-to', 'reason', 'severity' ),
                        array(
                            'status', 'EQ', $this->tr( 'Resolved', 'bug' )
                        )
                    )
                )
            ),
            array(
                $this->tr( 'Tasks' ),
                array(
                    'assigned-to' => array(
                        $this->tr( 'Assigned To', 'task' ),
                        'USER',
                        array(
                            'members' => 1
                        )
                    ),
                    'status' => array(
                        $this->tr( 'Status', 'task' ),
                        'ENUM',
                        array(
                            'items' => array( $this->tr( 'Active', 'task' ), $this->tr( 'Completed', 'task' ), $this->tr( 'Closed', 'task' ) ),
                            'required' => 1,
                            'default' => $this->tr( 'Active', 'task' )
                        )
                    ),
                    'priority' => array(
                        $this->tr( 'Priority', 'task' ),
                        'NUMERIC',
                        array(
                            'min-value' => '1',
                            'max-value' => '3',
                            'required' => 1,
                            'default' => '2'
                        )
                    ),
                    'progress' => array(
                        $this->tr( 'Progress' ),
                        'NUMERIC',
                        array(
                            'min-value' => '0',
                            'max-value' => '100',
                        )
                    ),
                    'due-date' => array(
                        $this->tr( 'Due Date' ),
                        'DATETIME',
                        array()
                    )
                ),
                array( 'assigned-to', 'status', 'priority' ),
                array(
                    array(
                        $this->tr( 'Created By Me', 'task' ),
                        array( 'assigned-to', 'status', 'priority' ),
                        array(
                            System_Api_Column::CreatedBy, 'EQ', '[Me]'
                        )
                    ),
                    array(
                        $this->tr( 'Active Tasks' ),
                        array( 'assigned-to', 'priority', 'progress', 'due-date' ),
                        array(
                            'status', 'EQ', $this->tr( 'Active', 'task' )
                        )
                    ),
                    array(
                        $this->tr( 'My Active Tasks' ),
                        array( 'priority', 'progress', 'due-date' ),
                        array(
                            'assigned-to', 'EQ', '[Me]',
                            'status', 'EQ', $this->tr( 'Active', 'task' )
                        )
                    ),
                    array(
                        $this->tr( 'Unassigned Tasks' ),
                        array( 'priority', 'due-date' ),
                        array(
                            'assigned-to', 'EQ', '',
                            'status', 'EQ', $this->tr( 'Active', 'task' )
                        )
                    ),
                    array(
                        $this->tr( 'Completed Tasks' ),
                        array( 'assigned-to', 'priority' ),
                        array(
                            'status', 'EQ', $this->tr( 'Completed', 'task' )
                        )
                    )
                )
            )
        );

        foreach ( $types as $type ) {
            $query = 'INSERT INTO {issue_types} ( type_name ) VALUES ( %s )';
            $this->connection->execute( $query, $type[ 0 ] );

            $typeId = $this->connection->getInsertId( 'issue_types', 'type_id' );

            $attributeIds = array();

            foreach ( $type[ 1 ] as $attributeKey => $attribute ) {
                $info = new System_Api_DefinitionInfo();
                $info->setType( $attribute[ 1 ] );
                foreach ( $attribute[ 2 ] as $key => $value )
                    $info->setMetadata( $key, $value );

                $query = 'INSERT INTO {attr_types} ( type_id, attr_name, attr_def ) VALUES ( %d, %s, %s )';
                $this->connection->execute( $query, $typeId, $attribute[ 0 ], $info->toString() );

                $attributeIds[ $attributeKey ] = $this->connection->getInsertId( 'attr_types', 'attr_id' );
            }

            if ( !empty( $attributeIds ) ) {
                $query = 'INSERT INTO {view_settings} ( type_id, set_key, set_value ) VALUES ( %d, %s, %s )';
                $this->connection->execute( $query, $typeId, 'attribute_order', join( ',', array_values( $attributeIds ) ) );
            }

            $columns = array( System_Api_Column::ID, System_Api_Column::Name, System_Api_Column::ModifiedDate, System_Api_Column::ModifiedBy );
            foreach ( $type[ 2 ] as $attributeKey )
                $columns[] = is_int( $attributeKey ) ? $attributeKey : System_Api_Column::UserDefined + $attributeIds[ $attributeKey ];

            $info = new System_Api_DefinitionInfo();
            $info->setType( 'VIEW' );
            $info->setMetadata( 'columns', join( ',', $columns ) );
            $info->setMetadata( 'sort-column', System_Api_Column::ID );

            $query = 'INSERT INTO {view_settings} ( type_id, set_key, set_value ) VALUES ( %d, %s, %s )';
            $this->connection->execute( $query, $typeId, 'default_view', $info->toString() );

            foreach ( $type[ 3 ] as $view ) {
                $columns = array( System_Api_Column::ID, System_Api_Column::Name, System_Api_Column::ModifiedDate, System_Api_Column::ModifiedBy );
                foreach ( $view[ 1 ] as $attributeKey )
                    $columns[] = is_int( $attributeKey ) ? $attributeKey : System_Api_Column::UserDefined + $attributeIds[ $attributeKey ];

                $filters = array();
                for ( $i = 0; $i < count( $view[ 2 ] ); $i += 3 ) {
                    $info = new System_Api_DefinitionInfo();
                    $info->setType( $view[ 2 ][ $i + 1 ] );
                    $info->setMetadata( 'column', is_int( $view[ 2 ][ $i ] ) ? $view[ 2 ][ $i ] : System_Api_Column::UserDefined + $attributeIds[ $view[ 2 ][ $i ] ] );
                    $info->setMetadata( 'value', $view[ 2 ][ $i + 2 ] );
                    $filters[] = $info->toString();
                }

                $info = new System_Api_DefinitionInfo();
                $info->setType( 'VIEW' );
                $info->setMetadata( 'columns', join( ',', $columns ) );
                $info->setMetadata( 'sort-column', System_Api_Column::ID );
                if ( !empty( $filters ) )
                    $info->setMetadata( 'filters', $filters );

                $query = 'INSERT INTO {views} ( type_id, user_id, view_name, view_def ) VALUES ( %d, NULL, %s, %s )';
                $this->connection->execute( $query, $typeId, $view[ 0 ], $info->toString() );
            }
        }
    }
}
