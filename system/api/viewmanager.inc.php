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
* Manage views and view settings.
*
* Like all API classes, this class does not check permissions to perform
* an operation and does not validate the input values. An error is thrown
* only if the requested object does not exist or is inaccessible.
*/
class System_Api_ViewManager extends System_Api_Base
{
    private static $settings = array();
    private static $views = array();

    /**
    * @name Flags
    */
    /*@{*/
    /** Permission to edit the view is required. */
    const AllowEdit = 1;
    /** Indicate a public view. */
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
    * Return all personal and public views for all issue types.
    * @return An array of associative arrays representing views.
    */
    public function getViews()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT view_id, type_id, view_name, view_def, ( CASE WHEN user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
            . ' FROM {views}'
            . ' WHERE user_id = %d OR user_id IS NULL';

        return $this->connection->queryTable( $query, $principal->getUserId() );
    }

    /**
    * Get the total number of personal views for given issue type.
    * @param $type Issue type for which views are retrieved.
    */
    public function getPersonalViewsCount( $type )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        $query = 'SELECT COUNT(*)'
            . ' FROM {views}'
            . ' WHERE type_id = %d AND user_id = %d';

        return $this->connection->queryScalar( $query, $typeId, $principal->getUserId() );
    }

    /**
    * Get the paged list of personal views for given issue type.
    * @param $type Issue type for which views are retrieved.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing views.
    */
    public function getPersonalViewsPage( $type, $orderBy, $limit, $offset )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        $query = 'SELECT view_id, view_name, view_def'
            . ' FROM {views}'
            . ' WHERE type_id = %d AND user_id = %d';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $typeId, $principal->getUserId() );
    }

    /**
    * Get the total number of public views for given issue type.
    * @param $type Issue type for which views are retrieved.
    */
    public function getPublicViewsCount( $type )
    {
        $typeId = $type[ 'type_id' ];

        $query = 'SELECT COUNT(*)'
            . ' FROM {views}'
            . ' WHERE type_id = %d AND user_id IS NULL';

        return $this->connection->queryScalar( $query, $typeId );
    }

    /**
    * Get the paged list public views for given issue type.
    * @param $type Issue type for which views are retrieved.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing views.
    */
    public function getPublicViewsPage( $type, $orderBy, $limit, $offset )
    {
        $typeId = $type[ 'type_id' ];

        $query = 'SELECT view_id, view_name, view_def'
            . ' FROM {views}'
            . ' WHERE type_id = %d AND user_id IS NULL';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $typeId );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getViewsColumns()
    {
        return array(
            'name' => 'view_name COLLATE LOCALE'
        );
    }

    /**
    * Get the view with given identifier.
    * @param $viewId Identifier of the view.
    * @param $flags If AllowEdit is passed an error is thrown if the user
    * does not have permission to edit the view.
    * @return Array representing the view.
    */
    public function getView( $viewId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        if ( isset( self::$views[ $viewId ] ) ) {
            $view = self::$views[ $viewId ];
        } else {
            $query = 'SELECT v.view_id, v.view_name, v.view_def, ( CASE WHEN v.user_id IS NULL THEN 1 ELSE 0 END ) AS is_public, t.type_id, t.type_name'
                . ' FROM {views} AS v'
                . ' JOIN {issue_types} AS t ON t.type_id = v.type_id'
                . ' WHERE v.view_id = %d AND ( user_id = %d OR user_id IS NULL )';

            if ( !( $view = $this->connection->queryRow( $query, $viewId, $principal->getUserId() ) ) )
                throw new System_Api_Error( System_Api_Error::UnknownView );

            self::$views[ $viewId ] = $view;
        }

        if ( ( $flags & self::AllowEdit ) && $view[ 'is_public' ] && !$principal->isAdministrator() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $view;
    }

    /**
    * Get the view with given identifier associated with specified issue type.
    * @param $type Issue type for which the view is retrieved.
    * @param $viewId Identifier of the view.
    * @param $flags If AllowEdit is passed an error is thrown if the user
    * does not have permission to edit the view. If IsPublic is passed
    * the view must be a public view.
    * @return Array representing the view.
    */
    public function getViewForIssueType( $type, $viewId, $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        if ( $flags & self::IsPublic || !$principal->isAuthenticated() ) {
            $query = 'SELECT view_id, type_id, view_name, view_def, 1 AS is_public'
                . ' FROM {views}'
                . ' WHERE view_id = %1d AND type_id = %2d AND user_id IS NULL';
        } else {
            $query = 'SELECT view_id, type_id, view_name, view_def, ( CASE WHEN user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
                . ' FROM {views}'
                . ' WHERE view_id = %1d AND type_id = %2d AND ( user_id = %3d OR user_id IS NULL )';
        }

        if ( !( $view = $this->connection->queryRow( $query, $viewId, $typeId, $principal->getUserId() ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownView );

        if ( ( $flags & self::AllowEdit ) && $view[ 'is_public' ] && !$principal->isAdministrator() )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        return $view;
    }

    /**
    * Check if the public view with given identifier associated with specified
    * issue type exists.
    * @param $type Issue type for which the view is retrieved.
    * @param $viewId Identifier of the view.
    * @return @c true if the view exists.
    */
    public function isPublicViewForIssueType( $type, $viewId )
    {
        $typeId = $type[ 'type_id' ];

        $query = 'SELECT view_id'
            . ' FROM {views}'
            . ' WHERE view_id = %d AND type_id = %d AND user_id IS NULL';

        return ( $this->connection->queryScalar( $query, $viewId, $typeId ) !== false );
    }

    /**
    * Get public views associated with specified issue type.
    * @param $type Issue type for which the view is retrieved.
    * @return An associative array of public views view.
    */
    public function getPublicViewsForIssueType( $type )
    {
        $typeId = $type[ 'type_id' ];

        $query = 'SELECT view_id, view_name'
            . ' FROM {views}'
            . ' WHERE type_id = %d AND user_id IS NULL'
            . ' ORDER BY view_name COLLATE LOCALE';

        $views = $this->connection->queryTable( $query, $typeId );

        $result = array();
        foreach ( $views as $view )
            $result[ $view[ 'view_id' ] ] = $view[ 'view_name' ];

        return $result;
    }

    /**
    * Get personal and public views for given issue type.
    * @param $type Issue type for which views are retrieved.
    * @return Array containing two associative arrays of personal views
    * and public views.
    */
    public function getViewsForIssueType( $type )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        if ( !$principal->isAuthenticated() ) {
            $query = 'SELECT view_id, view_name, 1 AS is_public'
                . ' FROM {views}'
                . ' WHERE type_id = %d AND user_id IS NULL';
        } else {
            $query = 'SELECT view_id, view_name, ( CASE WHEN user_id IS NULL THEN 1 ELSE 0 END ) AS is_public'
                . ' FROM {views}'
                . ' WHERE type_id = %d AND ( user_id = %d OR user_id IS NULL )';
        }
        $query .= ' ORDER BY view_name COLLATE LOCALE';

        $views = $this->connection->queryTable( $query, $typeId, $principal->getUserId() );

        $result = array();
        foreach ( $views as $view )
            $result[ $view[ 'is_public' ] ][ $view[ 'view_id' ] ] = $view[ 'view_name' ];

        return $result;
    }

    /**
    * Create a new personal view. An error is thrown if a view with given name
    * already exists.
    * @param $type Issue type for which the view is created.
    * @param $name The name of the view to create.
    * @param $definition The definition of the view.
    * @return The identifier of the new view.
    */
    public function addPersonalView( $type, $name, $definition )
    {
        $principal = System_Api_Principal::getCurrent();

        $typeId = $type[ 'type_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'views' );

        try {
            $query = 'SELECT view_id FROM {views} WHERE type_id = %d AND user_id = %d AND view_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $principal->getUserId(), $name ) !== false )
                throw new System_Api_Error( System_Api_Error::ViewAlreadyExists );

            $query = 'INSERT INTO {views} ( type_id, user_id, view_name, view_def ) VALUES ( %d, %d, %s, %s )';
            $this->connection->execute( $query, $typeId, $principal->getUserId(), $name, $definition );

            $viewId = $this->connection->getInsertId( 'views', 'view_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $viewId;
    }

    /**
    * Create a new public view. An error is thrown if a view with given name
    * already exists.
    * @param $type Issue type for which the view is created.
    * @param $name The name of the view to create.
    * @param $definition The definition of the view.
    * @return The identifier of the new view.
    */
    public function addPublicView( $type, $name, $definition )
    {
        $typeId = $type[ 'type_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'views' );

        try {
            $query = 'SELECT view_id FROM {views} WHERE type_id = %d AND user_id IS NULL AND view_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::ViewAlreadyExists );

            $query = 'INSERT INTO {views} ( type_id, user_id, view_name, view_def ) VALUES ( %d, NULL, %s, %s )';
            $this->connection->execute( $query, $typeId, $name, $definition );

            $viewId = $this->connection->getInsertId( 'views', 'view_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $viewId;
    }

    /**
    * Rename a public or personal view. An error is thrown if a view
    * with given name already exists.
    * @param $view The view to rename.
    * @param $newName The new name of the view.
    * @return @c true if the name was modified.
    */
    public function renameView( $view, $newName )
    {
        $principal = System_Api_Principal::getCurrent();

        $viewId = $view[ 'view_id' ];
        $typeId = $view[ 'type_id' ];
        $isPublic = $view[ 'is_public' ];
        $oldName = $view[ 'view_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'views' );

        try {
            $userId = $isPublic ? null : $principal->getUserId();

            $query = 'SELECT view_id FROM {views} WHERE type_id = %d AND user_id = %d? AND view_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $userId, $newName ) !== false )
                throw new System_Api_Error( System_Api_Error::ViewAlreadyExists );

            $query = 'UPDATE {views} SET view_name = %s WHERE view_id = %d';
            $this->connection->execute( $query, $newName, $viewId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return true;
    }

    /**
    * Modify the definition of a public or personal view.
    * @param $view The view to modify.
    * @param $newDefinition The new definition of the view.
    * @return @c true if the definition was modified.
    */
    public function modifyView( $view, $newDefinition )
    {
        $viewId = $view[ 'view_id' ];
        $oldDefinition = $view[ 'view_def' ];

        if ( $newDefinition == $oldDefinition )
            return false;

        $query = 'UPDATE {views} SET view_def = %s WHERE view_id = %d';
        $this->connection->execute( $query, $newDefinition, $viewId );

        return true;
    }

    /**
    * Convert a personal view to a public view.
    * @param $view The view to publish.
    * @return @c true if the view was published.
    */
    public function publishView( $view )
    {
        $viewId = $view[ 'view_id' ];
        $typeId = $view[ 'type_id' ];
        $isPublic = $view[ 'is_public' ];
        $name = $view[ 'view_name' ];

        if ( $isPublic )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'views' );

        try {
            $query = 'SELECT view_id FROM {views} WHERE type_id = %d AND user_id IS NULL AND view_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::ViewAlreadyExists );

            $query = 'UPDATE {views} SET user_id = NULL WHERE view_id = %d';
            $this->connection->execute( $query, $viewId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return true;
    }

    /**
    * Convert a public view to a personal view.
    * @param $view The view to unpublish.
    * @return @c true if the view was unpublished.
    */
    public function unpublishView( $view )
    {
        $principal = System_Api_Principal::getCurrent();

        $viewId = $view[ 'view_id' ];
        $typeId = $view[ 'type_id' ];
        $isPublic = $view[ 'is_public' ];
        $name = $view[ 'view_name' ];

        if ( !$isPublic )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'views' );

        try {
            $query = 'SELECT view_id FROM {views} WHERE type_id = %d AND user_id = %d AND view_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $principal->getUserId(), $name ) !== false )
                throw new System_Api_Error( System_Api_Error::ViewAlreadyExists );

            $query = 'DELETE FROM {alerts} WHERE view_id = %d AND user_id <> %d';
            $this->connection->execute( $query, $viewId, $principal->getUserId() );

            $query = 'UPDATE {views} SET user_id = %d WHERE view_id = %d';
            $this->connection->execute( $query, $principal->getUserId(), $viewId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return true;
    }

    /**
    * Delete a public or personal view.
    * @param $view The view to delete.
    * @return @c true if the view was deleted.
    */
    public function deleteView( $view )
    {
        $viewId = $view[ 'view_id' ];

        $query = 'DELETE FROM {views} WHERE view_id = %d';
        $this->connection->execute( $query, $viewId );

        return true;
    }

    /**
    * Return all view settings as an array of associative arrays.
    */
    public function getViewSettings()
    {
        $query = 'SELECT type_id, set_key, set_value'
            . ' FROM {view_settings}';

        return $this->connection->queryTable( $query );
    }

    /**
    * Get the specified view setting of the given issue type.
    * @param $type Issue type associated with the setting.
    * @param $key Name of the setting.
    * @return The value of the setting or @c null if it wasn't set.
    */
    public function getViewSetting( $type, $key )
    {
        $typeId = $type[ 'type_id' ];

        if ( isset( self::$settings[ $typeId ] ) ) {
            $settings = self::$settings[ $typeId ];
        } else {
            $query = 'SELECT set_key, set_value FROM {view_settings} WHERE type_id = %d';

            $table = $this->connection->queryTable( $query, $typeId );

            $settings = array();
            foreach ( $table as $row )
                $settings[ $row[ 'set_key' ] ] = $row[ 'set_value' ];

            self::$settings[ $typeId ] = $settings;
        }

        return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
    }

    /**
    * Set the specified view setting of the given issue type.
    * @param $type Issue type associated with the setting.
    * @param $key Name of the setting.
    * @param $newValue The new value of the setting.
    * @return @c true if the setting was changed.
    */
    public function setViewSetting( $type, $key, $newValue )
    {
        $oldValue = $this->getViewSetting( $type, $key );

        if ( $newValue == $oldValue )
            return false;

        $typeId = $type[ 'type_id' ];

        if ( $oldValue == '' )
            $query = 'INSERT INTO {view_settings} ( type_id, set_key, set_value ) VALUES ( %1d, %2s, %3s )';
        else if ( $newValue == '' )
            $query = 'DELETE FROM {view_settings} WHERE type_id = %1d AND set_key = %2s';
        else
            $query = 'UPDATE {view_settings} SET set_value = %3s WHERE type_id = %1d AND set_key = %2s';

        $this->connection->execute( $query, $typeId, $key, $newValue );

        self::$settings[ $typeId ][ $key ] = $newValue;

        return true;
    }

    /**
    * Sort rows by order of attributes for given issue type.
    * @param type Issue type related with the rows to sort.
    * @param rows Array of rows to sort.
    * @return Rows sorted by order of attributes.
    */
    public function sortByAttributeOrder( $type, $rows )
    {
        $order = $this->getViewSetting( $type, 'attribute_order' );

        if ( $order != null ) {
            $validator = new System_Api_Validator();
            $attributeIds = $validator->convertToIntArray( $order );

            $unordered = array();
            foreach ( $rows as $row )
                $unordered[ $row[ 'attr_id' ] ] = $row;

            $ordered = array();
            foreach ( $attributeIds as $attributeId ) {
                if ( isset( $unordered[ $attributeId ] ) ) {
                    $ordered[] = $unordered[ $attributeId ];
                    unset( $unordered[ $attributeId ] );
                }
            }

            $rows = array_merge( $ordered, $unordered );
        }

        return $rows;
    }
}
