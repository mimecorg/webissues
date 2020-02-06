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
* Universal database schema generator.
*
* This class generatates database dependent DDL queries based on a common
* field definition format. This is an abstract class; to create an instance
* of this class call System_Db_Connection::getSchemaGenerator().
*/
abstract class System_Db_SchemaGenerator
{
    protected $connection = null;

    protected $references = array();

    /**
    * Constructor.
    * @param $connection The associated database connection.
    */
    public function __construct( $connection )
    {
        $this->connection = $connection;
    }

    /**
    * Create a table with columns, constraints and indexes.
    * @param $tableName The name of the table.
    * @param $fields An associative array of field definitions.
    */
    public function createTable( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->prepareTableField( $tableName, $fieldName, $info );
        }

        $this->executeCreateTable( $tableName );
    }

    /**
    * Add a column, constraint or field to an existing table.
    * @param $tableName The name of the table.
    * @param $fields An associative array of field definitions.
    */
    public function addFields( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->prepareTableField( $tableName, $fieldName, $info );
        }

        $this->executeAddFields( $tableName );
    }

    /**
    * Add or drop the NOT NULL constraint for existing fields.
    * @param $tableName The name of the table.
    * @param $fields An associative array of field definitions.
    */
    public function modifyFieldsNull( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->prepareModifyFieldNull( $tableName, $fieldName, $info );
        }

        $this->executeAlterTable( $tableName );
    }

    /**
    * Modify the types of existing fields.
    * @param $tableName The name of the table.
    * @param $fields An associative array of field definitions.
    */
    public function modifyFieldsType( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->prepareModifyFieldType( $tableName, $fieldName, $info );
        }

        $this->executeAlterTable( $tableName );
    }

    /**
    * Remove the columns from an existing table.
    * @param $tableName The name of the table.
    * @param $fields An array of names of fields to remove.
    */
    public function removeFields( $tableName, $fields )
    {
        foreach ( $fields as $fieldName )
            $this->prepareRemoveField( $tableName, $fieldName );

        $this->executeAlterTable( $tableName );
    }

    /**
    * Remove the indexes from an existing table.
    * @param $tableName The name of the table.
    * @param $fields An associative array of index definitions.
    */
    public function removeIndexes( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->prepareRemoveIndex( $tableName, $fieldName, $info );
        }

        $this->executeAlterTable( $tableName );
    }

    /**
    * Add foreign keys to an existing table.
    * @param $tableName The name of the table.
    * @param $fields An associative array of fields with foreign keys.
    */
    public function addReferences( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->processReference( $tableName, $fieldName, $info );
        }
    }

    /**
    * Remove foreign keys from an existing table.
    * @param $tableName The name of the table.
    * @param $fields An associative array of fields with foreign keys.
    */
    public function removeReferences( $tableName, $fields )
    {
        foreach ( $fields as $fieldName => $definition ) {
            $info = System_Api_DefinitionInfo::fromString( $definition );
            $this->prepareRemoveReference( $tableName, $fieldName, $info );
        }

        $this->executeAlterTable( $tableName );
    }

    /**
    * Create a view.
    * @param $viewName The name of the view.
    * @param $query The query defining the view.
    */
    public function createView( $viewName, $query )
    {
        $viewQuery = 'CREATE VIEW {' . $viewName . '} AS ' . $query;
        $this->connection->execute( $viewQuery );
    }

    protected abstract function prepareTableField( $tableName, $fieldName, $info );

    protected abstract function executeCreateTable( $tableName );

    protected abstract function executeAddFields( $tableName );

    protected abstract function prepareModifyFieldNull( $tableName, $fieldName, $info );

    protected abstract function prepareModifyFieldType( $tableName, $fieldName, $info );

    protected abstract function prepareRemoveField( $tableName, $fieldName );

    protected abstract function prepareRemoveIndex( $tableName, $fieldName, $info );

    protected abstract function prepareRemoveReference( $tableName, $fieldName, $info );

    protected abstract function executeAlterTable( $tableName );

    protected abstract function processReference( $tableName, $fieldName, $info );

    /**
    * Set identity insert on or off for the given table.
    */
    public function setIdentityInsert( $tableName, $fieldName, $on )
    {
    }

    /**
    * Create foreign key references.
    * This method must be called after creating and updating all tables.
    */
    public function updateReferences()
    {
        foreach ( $this->references as $reference )
            $this->connection->execute( $reference );

        $this->references = array();
    }
}
