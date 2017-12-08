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
class System_Api_TypeManager extends System_Api_Base
{
    /**
    * @name Flags
    */
    /*@{*/
    /** Force deletion with all associated data. */
    const ForceDelete = 1;
    /*@}*/

    private static $attributeTypes = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get list of issue types.
    * @return An array of associative arrays representing types.
    */
    public function getIssueTypes()
    {
        $query = 'SELECT type_id, type_name FROM {issue_types} ORDER BY type_name COLLATE LOCALE';

        return $this->connection->queryTable( $query );
    }

    /**
    * Get the issue type with given identifier.
    * @param $typeId Identifier of the type.
    * @return Array containing the issue type.
    */
    public function getIssueType( $typeId )
    {
        $query = 'SELECT type_id, type_name FROM {issue_types} WHERE type_id = %d';

        if ( !( $type = $this->connection->queryRow( $query, $typeId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownType );

        return $type;
    }

    /**
    * Get list of attributes from all issue types.
    * @return An array of associative arrays representing attributes.
    */
    public function getAttributeTypes()
    {
        $query = 'SELECT attr_id, type_id, attr_name, attr_def FROM {attr_types} ORDER BY attr_name COLLATE LOCALE';

        return $this->connection->queryTable( $query );
    }

    /**
    * Get the attribute with given identifier.
    * @param $attributeId Identifier of the attribute.
    * @return Array containing the attribute.
    */
    public function getAttributeType( $attributeId )
    {
        $query = 'SELECT a.attr_id, t.type_id, a.attr_name, a.attr_def, t.type_name'
            . ' FROM {attr_types} AS a'
            . ' INNER JOIN {issue_types} AS t ON t.type_id = a.type_id'
            . ' WHERE attr_id = %d';

        if ( !( $attribute = $this->connection->queryRow( $query, $attributeId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownAttribute );

        return $attribute;
    }

    /**
    * Get an attribute for the given issue.
    * @param $issue Issue associated with the attribute.
    * @param $attributeId Identifier of the attribute.
    * @return Array containing the attribute.
    */
    public function getAttributeTypeForIssue( $issue, $attributeId )
    {
        $typeId = $issue[ 'type_id' ];

        $query = 'SELECT attr_id, type_id, attr_name, attr_def FROM {attr_types} WHERE attr_id = %d AND type_id = %d';

        if ( !( $attribute = $this->connection->queryRow( $query, $attributeId, $typeId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownAttribute );

        return $attribute;
    }

    /**
    * Get default attribute values for issues in given folder.
    * @param $folder Folder containing the issue.
    * @return An associative array containing default values of attributes.
    */
    public function getDefaultAttributeValuesForFolder( $folder )
    {
        $typeId = $folder[ 'type_id' ];

        $query = 'SELECT attr_id, attr_def FROM {attr_types} WHERE type_id = %d';

        $attributes = $this->connection->queryTable( $query, $typeId );

        $values = array();

        foreach ( $attributes as $attribute ) {
            $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
            $value = $info->getMetadata( 'default' );
            if ( $value != '' )
                $values[ $attribute[ 'attr_id' ] ] = $this->convertInitialValue( $info, $value );
        }

        return $values;
    }

    /**
    * Convert expression in initial value of an attribute to actual value.
    * @param $info Definition of the attribute type.
    * @param $value The initial value of the attribute type.
    * @return The converted value.
    */
    public function convertInitialValue( $info, $value )
    {
        $type = $info->getType();

        if ( ( $type == 'TEXT' || $type == 'ENUM' || $type == 'USER' ) && mb_substr( $value, 0, 4 ) == '[Me]' ) {
            $principal = System_Api_Principal::getCurrent();
            return $principal->getUserName();
        }

        if ( $type == 'DATETIME' && mb_substr( $value, 0, 7 ) == '[Today]' ) {
            $date = new DateTime();

            if ( $info->getMetadata( 'local', 0 ) ) {
                $date->setTimezone( new DateTimeZone( 'UTC' ) );
            } else {
                $locale = new System_Api_Locale();
                $date->setTimezone( new DateTimeZone( $locale->getSetting( 'time_zone' ) ) );
            }

            $offset = mb_substr( $value, 7 );
            if ( $offset != '' )
                $date->modify( $offset . ' days' );

            if ( $info->getMetadata( 'time', 0 ) )
                return $date->format( 'Y-m-d H:i' );
            else
                return $date->format( 'Y-m-d' );
        }

        return $value;
    }

    /**
    * Get all attributes for given issue type.
    * @param $type The issue type containing the attributes.
    * @return An array of associative arrays representing attributes.
    */
    public function getAttributeTypesForIssueType( $type )
    {
        $typeId = $type[ 'type_id' ];

        if ( isset( self::$attributeTypes[ $typeId ] ) ) {
            $attributes = self::$attributeTypes[ $typeId ];
        } else {
            $query = 'SELECT attr_id, attr_name, attr_def FROM {attr_types} WHERE type_id = %d ORDER BY attr_name COLLATE LOCALE';

            $attributes = $this->connection->queryTable( $query, $typeId );

            self::$attributeTypes[ $typeId ] = $attributes;
        }

        return $attributes;
    }

    /**
    * Get all attributes for given issue types.
    * @param $types Array of issue types.
    * @return An array of associative arrays representing attributes.
    */
    public function getAttributeTypesForIssueTypes( $types )
    {
        if ( empty( $types ) )
            return array();

        $ids = array();
        foreach ( $types as $type )
            $ids[] = $type[ 'type_id' ];

        $query = 'SELECT attr_id, type_id, attr_name, attr_def FROM {attr_types}'
            . ' WHERE type_id IN ( %%d )'
            . 'ORDER BY attr_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $ids );
    }

    /**
    * Get the issue type for the given issue.
    * @param $issue The issue to retrieve the type from.
    * @return Array representing the issue type.
    */
    public function getIssueTypeForIssue( $issue )
    {
        $type[ 'type_id' ] = $issue[ 'type_id' ];
        $type[ 'type_name' ] = $issue[ 'type_name' ];
        return $type;
    }

    /**
    * Get the issue type for the given folder.
    * @param $folder The folder to retrieve the type from.
    * @return Array representing the issue type.
    */
    public function getIssueTypeForFolder( $folder )
    {
        $type[ 'type_id' ] = $folder[ 'type_id' ];
        $type[ 'type_name' ] = $folder[ 'type_name' ];
        return $type;
    }

    /**
    * Get the issue type for the given view.
    * @param $view The view to retrieve the type from.
    * @return Array representing the issue type.
    */
    public function getIssueTypeForView( $view )
    {
        $type[ 'type_id' ] = $view[ 'type_id' ];
        $type[ 'type_name' ] = $view[ 'type_name' ];
        return $type;
    }

    /**
    * Get list of issue types available to the current user.
    * @return An array of associative arrays representing types.
    */
    public function getAvailableIssueTypes()
    {
        $principal = System_Api_Principal::getCurrent();

        $query = 'SELECT t.type_id, t.type_name FROM {issue_types} AS t';
        if ( !$principal->isAuthenticated() ) {
            $query .= ' WHERE t.type_id IN ( SELECT f.type_id FROM {folders} AS f'
                . ' JOIN {projects} AS p ON p.project_id = f.project_id'
                . ' WHERE p.is_public = 1 AND p.is_archived = 0 )';
        } else if ( !$principal->isAdministrator() ) {
            $query .= ' WHERE t.type_id IN ( SELECT f.type_id FROM {folders} AS f'
                . ' JOIN {projects} AS p ON p.project_id = f.project_id'
                . ' JOIN {effective_rights} AS r ON r.project_id = f.project_id AND r.user_id = %d'
                . ' WHERE p.is_archived = 0 )';
        }
        $query .= ' ORDER BY t.type_name COLLATE LOCALE';

        return $this->connection->queryTable( $query, $principal->getUserId() );
    }

    /**
    * Create a new issue type. An error is thrown if a type with given name
    * already exists.
    * @param $name The name of the issue type to create.
    * @return The identifier of the new type.
    */
    public function addIssueType( $name )
    {
        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'issue_types' );

        try {
            $query = 'SELECT type_id FROM {issue_types} WHERE type_name = %s';
            if ( $this->connection->queryScalar( $query, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::TypeAlreadyExists );

            $query = 'INSERT INTO {issue_types} ( type_name ) VALUES ( %s )';
            $this->connection->execute( $query, $name );

            $typeId = $this->connection->getInsertId( 'issue_types', 'type_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Added issue type "%1"', null, $name ) );

        return $typeId;
    }

    /**
    * Rename an issue type. An error is thrown if another type with given name
    * already exists.
    * @param $type The issue type to rename.
    * @param $newName The new name of the type.
    * @return @c true if the name was modified.
    */
    public function renameIssueType( $type, $newName )
    {
        $typeId = $type[ 'type_id' ];
        $oldName = $type[ 'type_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'issue_types' );

        try {
            $query = 'SELECT type_id FROM {issue_types} WHERE type_name = %s';
            if ( $this->connection->queryScalar( $query, $newName ) !== false )
                throw new System_Api_Error( System_Api_Error::TypeAlreadyExists );

            $query = 'UPDATE {issue_types} SET type_name = %s WHERE type_id = %d';
            $this->connection->execute( $query, $newName, $typeId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Renamed issue type "%1" to "%2"', null, $oldName, $newName ) );

        return true;
    }

    /**
    * Delete an issue type.
    * @param $type The issue type to delete.
    * @param $flags If ForceDelete is passed the issue type is deleted
    * even if has associated folders. Otherwise an error is thrown.
    * @return @c true if the type was deleted.
    */
    public function deleteIssueType( $type, $flags = 0 )
    {
        $typeId = $type[ 'type_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'folders' );

        try {
            if ( !( $flags & self::ForceDelete ) && $this->checkIssueTypeUsed( $type ) )
                throw new System_Api_Error( System_Api_Error::CannotDeleteType );

            $query = 'SELECT fl.file_id FROM {files} AS fl'
                . ' JOIN {changes} ch ON ch.change_id = fl.file_id'
                . ' JOIN {issues} i ON i.issue_id = ch.issue_id'
                . ' JOIN {folders} f ON f.folder_id = i.folder_id'
                . ' WHERE f.type_id = %d AND fl.file_storage = %d';
            $files = $this->connection->queryTable( $query, $typeId, System_Api_IssueManager::FileSystemStorage );

            $query = 'DELETE FROM {issue_types} WHERE type_id = %d';
            $this->connection->execute( $query, $typeId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        if ( $flags & self::ForceDelete ) {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Warning,
                $eventLog->tr( 'Deleted issue type "%1" with folders', null, $type[ 'type_name' ] ) );
        } else {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Deleted issue type "%1"', null, $type[ 'type_name' ] ) );
        }

        $issueManager = new System_Api_IssueManager();
        $issueManager->deleteFiles( $files );

        return true;
    }

    /**
    * Check if the issue type is used.
    * @return @c true if the issue type has associated folders.
    */
    public function checkIssueTypeUsed( $type )
    {
        $typeId = $type[ 'type_id' ];

        $query = 'SELECT COUNT(*) FROM {folders} WHERE type_id = %d';

        return $this->connection->queryScalar( $query, $typeId ) > 0;
    }

    /**
    * Add an attributte to the given issue type. An error is thrown if
    * an attribute with given name already exists in the type.
    * @param $type The type to which the attribute is added.
    * @param $name The name of the attribute to create.
    * @param $definition The type definition of the attribute.
    * @return The identifier of the new attribute.
    */
    public function addAttributeType( $type, $name, $definition )
    {
        $typeId = $type[ 'type_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'attr_types' );

        try {
            $query = 'SELECT attr_id FROM {attr_types} WHERE type_id = %d AND attr_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $name ) !== false )
                throw new System_Api_Error( System_Api_Error::AttributeAlreadyExists );

            $query = 'INSERT INTO {attr_types} ( type_id, attr_name, attr_def ) VALUES ( %d, %s, %s )';
            $this->connection->execute( $query, $typeId, $name, $definition );

            $attributeId = $this->connection->getInsertId( 'attr_types', 'attr_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Added attribute "%1" to issue type "%2"', null, $name, $type[ 'type_name' ] ) );

        return $attributeId;
    }

    /**
    * Rename an attribute. An error is thrown if another attribute with given
    * name already exists in the issue type.
    * @param $attribute The attribute to rename.
    * @param $newName The new name of the attribute.
    * @return @c true if the name was modified.
    */
    public function renameAttributeType( $attribute, $newName )
    {
        $attributeId = $attribute[ 'attr_id' ];
        $typeId = $attribute[ 'type_id' ];
        $oldName = $attribute[ 'attr_name' ];

        if ( $newName == $oldName )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::RepeatableRead, 'attr_types' );

        try {
            $query = 'SELECT attr_id FROM {attr_types} WHERE type_id = %d AND attr_name = %s';
            if ( $this->connection->queryScalar( $query, $typeId, $newName ) !== false )
                throw new System_Api_Error( System_Api_Error::AttributeAlreadyExists );

            $query = 'UPDATE {attr_types} SET attr_name = %s WHERE attr_id = %d';
            $this->connection->execute( $query, $newName, $attributeId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Renamed attribute "%1" to "%2" of issue type "%3"', null, $oldName, $newName, $attribute[ 'type_name' ] ) );

        return true;
    }

    /**
    * Modify the type definition of an attribute.
    * @param $attribute The attribute to modify.
    * @param $newDefinition The new type definition of the attribute.
    * @return @c true if the type was modified.
    */
    public function modifyAttributeType( $attribute, $newDefinition )
    {
        $attributeId = $attribute[ 'attr_id' ];
        $oldDefinition = $attribute[ 'attr_def' ];

        if ( $newDefinition == $oldDefinition )
            return false;

        $query = 'UPDATE {attr_types} SET attr_def = %s WHERE attr_id = %d';
        $this->connection->execute( $query, $newDefinition, $attributeId );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Modified attribute "%1" of issue type "%2"', null, $attribute[ 'attr_name' ], $attribute[ 'type_name' ] ) );

        return true;
    }

    /**
    * Delete an attribute.
    * @param $attribute The attribute to delete.
    * @param $flags If ForceDelete is passed the attribute is deleted
    * even if has associated issues. Otherwise an error is thrown.
    * @return @c true if the attribute was deleted.
    */
    public function deleteAttributeType( $attribute, $flags = 0 )
    {
        $attributeId = $attribute[ 'attr_id' ];

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'attr_values' );

        try {
            if ( !( $flags & self::ForceDelete ) && $this->checkAttributeTypeUsed( $attribute ) )
                throw new System_Api_Error( System_Api_Error::CannotDeleteAttribute );

            $query = 'DELETE FROM {attr_types} WHERE attr_id = %d';
            $this->connection->execute( $query, $attributeId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        $eventLog = new System_Api_EventLog( $this );
        if ( $flags & self::ForceDelete ) {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Warning,
                $eventLog->tr( 'Deleted attribute "%1" with values from issue type "%2"', null, $attribute[ 'attr_name' ], $attribute[ 'type_name' ] ) );
        } else {
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Deleted attribute "%1" from issue type "%2"', null, $attribute[ 'attr_name' ], $attribute[ 'type_name' ] ) );
        }

        return true;
    }

    /**
    * Check if the attribute is used.
    * @return @c true if the attribute has associated issues.
    */
    public function checkAttributeTypeUsed( $attribute )
    {
        $attributeId = $attribute[ 'attr_id' ];

        $query = 'SELECT COUNT(*) FROM {attr_values} WHERE attr_id = %d';

        return $this->connection->queryScalar( $query, $attributeId ) > 0;
    }

    /**
    * Get the total number of issue types.
    */
    public function getIssueTypesCount()
    {
        $query = 'SELECT COUNT(*) FROM {issue_types}';

        return $this->connection->queryScalar( $query );
    }

    /**
    * Get a paged list of issue types.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing types.
    */
    public function getIssueTypesPage( $orderBy, $limit, $offset )
    {
        $query = 'SELECT type_id, type_name FROM {issue_types}';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getIssueTypesColumns()
    {
        return array(
            'name' => 'type_name COLLATE LOCALE'
        );
    }
}
