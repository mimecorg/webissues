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
* Schema generator for PostgreSQL.
*/
class System_Db_Pgsql_SchemaGenerator extends System_Db_SchemaGenerator
{
    private $fields = array();
    private $indexes = array();
    private $alters = array();

    /**
    * Constructor.
    */
    public function __construct( $connection )
    {
        parent::__construct( $connection );
    }

    protected function prepareTableField( $tableName, $fieldName, $info )
    {
        switch ( $info->getType() ) {
            case 'PRIMARY':
                $columns = $info->getMetadata( 'columns' );
                $this->fields[] = 'PRIMARY KEY ( ' . join( ', ', $columns ) . ' )';
                break;

            case 'INDEX':
                $columns = $info->getMetadata( 'columns' );
                $unique = $info->getMetadata( 'unique', 0 );
                if ( $unique )
                    $this->fields[] = 'CONSTRAINT {' . $tableName . '}_' . $fieldName . ' UNIQUE ( ' . join( ', ', $columns ) . ' )';
                else
                    $this->indexes[] = 'CREATE INDEX {' . $tableName . '}_' . $fieldName . ' ON {' . $tableName . '} ( ' . join( ', ', $columns ) . ' )';
                break;

            default:
                $this->fields[] = $fieldName . ' ' . $this->getFieldType( $info );
                $this->processReference( $tableName, $fieldName, $info );
                break;
        }
    }

    protected function executeCreateTable( $tableName )
    {
        $query = 'CREATE TABLE {' . $tableName . '} (' . "\n  " . join( ",\n  ", $this->fields ) . "\n" . ')';
        $this->connection->execute( $query );

        foreach ( $this->indexes as $index )
            $this->connection->execute( $index );

        $this->fields = array();
        $this->indexes = array();
    }

    protected function executeAddFields( $tableName )
    {
        foreach ( $this->fields as $field )
            $this->alters[] = 'ADD ' . $field;

        $this->executeAlterTable( $tableName );

        $this->fields = array();
    }

    protected function prepareModifyFieldNull( $tableName, $fieldName, $info )
    {
        if ( $info->getMetadata( 'null', 0 ) )
            $this->alters[] = 'ALTER ' . $fieldName . ' DROP NOT NULL';
        else
            $this->alters[] = 'ALTER ' . $fieldName . ' SET NOT NULL';
    }

    protected function prepareModifyIndexColumns( $tableName, $fieldName, $info )
    {
        $columns = $info->getMetadata( 'columns' );
        $unique = $info->getMetadata( 'unique', 0 );
        if ( $unique ) {
            $this->alters[] = 'DROP CONSTRAINT {' . $tableName . '}_' . $fieldName;
            $this->alters[] = 'ADD CONSTRAINT {' . $tableName . '}_' . $fieldName . ' UNIQUE ( ' . join( ', ', $columns ) . ' )';
        } else {
            $this->indexes[] = 'DROP INDEX {' . $tableName . '}_' . $fieldName;
            $this->indexes[] = 'CREATE INDEX {' . $tableName . '}_' . $fieldName . ' ON {' . $tableName . '} ( ' . join( ', ', $columns ) . ' )';
        }
    }

    protected function executeAlterTable( $tableName )
    {
        if ( !empty( $this->alters ) ) {
            $query = 'ALTER TABLE {' . $tableName . '} ' . join( ', ', $this->alters );
            $this->connection->execute( $query );
        }

        foreach ( $this->indexes as $index )
            $this->connection->execute( $index );

        $this->alters = array();
        $this->indexes = array();
    }

    public function setIdentityInsert( $tableName, $fieldName, $on )
    {
        if ( $on == false ) {
            $query = "SELECT setval('{" . $tableName . '}_' . $fieldName . "_seq', max(" . $fieldName . ')) FROM {' . $tableName . '}';
            $this->connection->execute( $query );
        }
    }

    private function getFieldType( $info )
    {
        switch ( $info->getType() ) {
            case 'SERIAL':
                return 'serial';

            case 'INTEGER':
                return $this->getIntegerType( $info->getMetadata( 'size', 'normal' ),
                    $info->getMetadata( 'null', 0 ), $info->getMetadata( 'default' ) );

            case 'CHAR':
                return $this->getCharType( 'char', $info->getMetadata( 'length', 255 ),
                    $info->getMetadata( 'null', 0 ), $info->getMetadata( 'default' ) );                            

            case 'VARCHAR':
                return $this->getCharType( 'varchar', $info->getMetadata( 'length', 255 ),
                    $info->getMetadata( 'null', 0 ), $info->getMetadata( 'default' ) );                            

            case 'TEXT':
                return $this->getTextType( 'text', $info->getMetadata( 'null', 0 ) );

            case 'BLOB':
                return $this->getTextType( 'bytea', $info->getMetadata( 'null', 0 ) );

            default:
                throw new System_Db_Exception( "Unknown field type '" . $info->getType() . "'" );
        }
    }

    private function getIntegerType( $size, $null, $default )
    {
        static $intTypes = array( 'tiny' => 'smallint', 'small' => 'smallint', 'medium' => 'integer', 'normal' => 'integer', 'big' => 'bigint' );

        $type = $intTypes[ $size ];
        if ( !$null )
            $type .= ' NOT NULL';
        if ( $default !== null )
            $type .= ' default ' . (int)$default;
        return $type;
    }

    private function getCharType( $type, $length, $null, $default )
    {
        $type .= '(' . $length . ')';
        if ( !$null )
            $type .= ' NOT NULL';
        if ( $default !== null )
            $type .= ' default \'' . $default . '\'';
        return $type;
    }

    public function getTextType( $type, $null )
    {
        if ( !$null )
            $type .= ' NOT NULL';
        return $type;
    }

    private function processReference( $tableName, $fieldName, $info )
    {
        $refTable = $info->getMetadata( 'ref-table' );

        if ( $refTable != null ) {
            $refColumn = $info->getMetadata( 'ref-column' );
            $onDelete = $info->getMetadata( 'on-delete', 'restrict' );

            $query = 'ALTER TABLE {' . $tableName . '} ADD CONSTRAINT {' . $tableName . '}_' . $fieldName . '_fk FOREIGN KEY ( '
                . $fieldName . ' ) REFERENCES {' . $refTable . '} ( ' . $refColumn . ' )';
            if ( $onDelete == 'cascade' )
                $query .= ' ON DELETE CASCADE';
            else if ( $onDelete == 'set-null' )
                $query .= ' ON DELETE SET NULL';

            $this->references[] = $query;
        }
    }
}
