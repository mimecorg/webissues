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
* Back-end engine for PostgreSQL database.
*
* This engine requires the mysqli PHP module and MySQL version 4.1 or newer.
*/
class System_Db_Pgsql_Engine implements System_Db_IEngine
{
    private $connection = null;
    private $result = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    public function open( $host, $database, $user, $password )
    {
        $port = '';

        $parts = explode( ':', $host );
        if ( isset( $parts[ 1 ] ) ) {
            $host = $parts[ 0 ];
            $port = $parts[ 1 ];
        }

        $string = "host='$host' port='$port' dbname='$database' user='$user' password='$password'";

        $this->connection = @pg_connect( $string );

        if ( $this->connection == false )
            throw new System_Db_Exception( 'Connection to database failed' );
    }

    public function close()
    {
        if ( $this->result ) {
            pg_free_result( $this->result );
            $this->result = null;
        }

        pg_close( $this->connection );
        $this->connection = null;
    }

    public function execute( $query, $params )
    {
        $this->result = $this->executeQuery( $query, $params );
    }

    public function query( $query, $params )
    {
        return new System_Db_Pgsql_Result( $this->executeQuery( $query, $params ) );
    }

    private function executeQuery( $query, $params )
    {
        if ( $this->result ) {
            pg_free_result( $this->result );
            $this->result = null;
        }

        return $this->sendQuery( $query, $params );
    }

    public function escapeArgument( $arg, $type, &$params )
    {
        switch( $type ) {
            case 'd':
                $params[] = (int)$arg;
                return '$' . count( $params ) . '::int';
            case 'f':
                $params[] = (float)$arg;
                return '$' . count( $params ) . '::decimal(14,6)';
            case 's':
                $params[] = (string)$arg;
                return '$' . count( $params ) . '::text';
            case 't':
                $params[] = (string)$arg;
                return '$' . count( $params ) . '::timestamp';
            case 'b':
                $data = pg_escape_bytea( $this->connection, $arg->getData() );
                $data = str_replace( array( "\\\\", "''" ), array( "\\", "'" ), $data );
                $params[] = $data;
                return '$' . count( $params ) . '::bytea';
        }
    }

    public function getPagedQuery( $query, $orderBy, $limit, $offset )
    {
        if ( $offset != 0 )
            $limit = "$limit OFFSET $offset";
        return "$query ORDER BY $orderBy LIMIT $limit";
    }

    public function getLocaleCollation()
    {
        return '';
    }

    public function castExpression( $expression, $type )
    {
        switch ( $type ) {
            case 'd':
                return "CAST( $expression AS int )";
            case 'f':
                return "CAST( $expression AS decimal(14,6) )";
            case 's':
                return "CAST( $expression AS text )";
            case 't':
                return "CAST( $expression AS timestamp )";
            default:
                throw new System_Db_Exception( 'Invalid type' );
        }
    }

    public function createAttachment( $data, $size, $fileName )
    {
        if ( substr( $data, 0, 2 ) == '\\x' )
            $data = pack( 'H*', substr( $data, 2 ) );
        else
            $data = pg_unescape_bytea( $data );
        return new System_Core_Attachment( $data, $size, $fileName );
    }

    public function getAffectedRows()
    {
        return pg_affected_rows( $this->result );
    }

    public function getInsertId( $table, $column )
    {
        $query = "SELECT currval('${table}_${column}_seq')";
        $result = $this->sendQuery( $query );
        $row = pg_fetch_row( $result );
        pg_free_result( $result );
        return $row[ 0 ];
    }

    public function checkTableExists( $table )
    {
        $query = "SELECT relname FROM pg_class WHERE relkind = 'r' AND relname = '$table'";
        $result = $this->sendQuery( $query );
        $count = pg_num_rows( $result );
        pg_free_result( $result );
        return $count > 0;
    }

    public function getParameter( $key )
    {
        switch ( $key ) {
            case 'server':
                return 'PostgreSQL';
            case 'version':
                $version = pg_version( $this->connection );
                return $version[ 'server' ];
            default:
                return null;
        }
    }

    public function beginTransaction( $level, $table )
    {
        switch ( $level ) {
            case System_Db_Transaction::ReadUncommitted:
            case System_Db_Transaction::ReadCommitted:
                $isoLevel = 'READ COMMITTED';
                break;
            case System_Db_Transaction::RepeatableRead:
            case System_Db_Transaction::Serializable:
                $isoLevel = 'SERIALIZABLE';
                break;
            default:
                throw new System_Db_Exception( 'Unsupported isolation level' );
        }

        $result = $this->sendQuery( "BEGIN ISOLATION LEVEL $isoLevel" );
        pg_free_result( $result );

        if ( $isoLevel == 'SERIALIZABLE' && $table != null ) {
            try {
                $result = $this->sendQuery( "LOCK TABLE $table IN SHARE MODE" );
                pg_free_result( $result );
            } catch ( Exception $ex ) {
                $this->endTransaction( false );
                throw $ex;
            }
        }
    }

    public function endTransaction( $commit )
    {
        if ( $commit )
            $result = $this->sendQuery( 'COMMIT' );
        else
            $result = $this->sendQuery( 'ROLLBACK' );
        pg_free_result( $result );
    }

    private function sendQuery( $query, $params = null )
    {
        if ( empty( $params ) )
            $sent = pg_send_query( $this->connection, $query );
        else
            $sent = pg_send_query_params( $this->connection, $query, $params );

        if ( !$sent )
            throw new System_Db_Exception( 'Cannot send query' );

        $result = pg_get_result( $this->connection );

        if ( !$result )
            throw new System_Db_Exception( 'Cannot retrieve result' );

        $status = pg_result_status( $result );

        if ( $status != PGSQL_COMMAND_OK && $status != PGSQL_TUPLES_OK ) {
            $errno = pg_result_error_field( $result, PGSQL_DIAG_SQLSTATE );
            $error = pg_result_error_field( $result, PGSQL_DIAG_MESSAGE_PRIMARY );

            pg_free_result( $result );

            if ( $errno == '40P01' )
                throw new System_Api_Error( System_Api_Error::TransactionDeadlock, new System_Db_Exception( $error ) );
            if ( $errno == '23503' )
                throw new System_Api_Error( System_Api_Error::ConstraintConflict, new System_Db_Exception( $error ) );

            throw new System_Db_Exception( $error );
        }

        return $result;
    }
}
