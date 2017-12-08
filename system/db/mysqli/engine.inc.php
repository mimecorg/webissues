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
* Back-end engine for MySQL database.
*
* This engine requires the mysqli PHP module and MySQL version 4.1 or newer.
*/
class System_Db_Mysqli_Engine implements System_Db_IEngine
{
    private $connection = null;
    private $statement = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    public function open( $host, $database, $user, $password )
    {
        $port = '';
        $socket = '';

        $parts = explode( ':', $host );
        if ( isset( $parts[ 1 ] ) ) {
            $host = $parts[ 0 ];
            if ( is_numeric( $parts[ 1 ] ) )
                $port = $parts[ 1 ];
            else
                $socket = $parts[ 1 ];
        }

        if ( $host == '' )
            $host = 'localhost';

        if ( $socket != '' )
            $this->connection = @new mysqli( $host, $user, $password, $database, null, $socket );
        else if ( $port != '' )
            $this->connection = @new mysqli( $host, $user, $password, $database, $port );
        else
            $this->connection = @new mysqli( $host, $user, $password, $database );

        if ( mysqli_connect_error() )
            throw new System_Db_Exception( mysqli_connect_error() );

        $this->connection->set_charset( 'utf8' );
    }

    public function close()
    {
        $this->connection->close();
        $this->connection = null;
    }

    public function execute( $query, $params )
    {
        if ( empty( $params ) )
            $this->executeQuery( $query );
        else
            $this->executeStatement( $query, $params );
    }

    public function query( $query, $params )
    {
        if ( empty( $params ) ) {
            $rs = $this->executeQuery( $query );
            return new System_Db_Mysqli_Result( $rs );
        } else {
            $this->executeStatement( $query, $params );
            return new System_Db_Mysqli_Statement( $this->statement );
        }
    }

    private function executeQuery( $query )
    {
        $this->statement = null;

        $rs = $this->connection->query( $query );
        if ( !$rs )
            $this->handleError( $this->connection );

        return $rs;
    }

    private function executeStatement( $query, $params )
    {
        $this->statement = $this->connection->prepare( $query );
        if ( !$this->statement )
            $this->handleError( $this->connection );

        $types = '';
        foreach ( $params as $param ) {
            if ( is_int( $param ) )
                $types .= 'i';
            else if ( is_float( $param ) )
                $types .= 'd';
            else
                $types .= 's';
        }

        $args = array( $this->statement, $types );
        foreach ( $params as $key => $param )
            $args[ $key + 2 ] =& $params[ $key ];

        call_user_func_array( 'mysqli_stmt_bind_param', $args );

        if ( !$this->statement->execute() )
            $this->handleError( $this->statement );
    }

    public function escapeArgument( $arg, $type, &$params )
    {
        switch( $type ) {
            case 'd':
                $params[] = (int)$arg;
                return '?';
            case 'f':
                $params[] = (float)$arg;
                return '?';
            case 's':
            case 't':
                $params[] = (string)$arg;
                return '?';
            case 'b':
                $params[] = $arg->getData();
                return '?';
        }
    }

    public function getPagedQuery( $query, $orderBy, $limit, $offset )
    {
        if ( $offset != 0 )
            $limit = "$offset, $limit";
        return "$query ORDER BY $orderBy LIMIT $limit";
    }

    public function getLocaleCollation()
    {
        return 'utf8_unicode_ci';
    }

    public function createAttachment( $data, $size, $fileName )
    {
        return new System_Core_Attachment( $data, $size, $fileName );
    }

    public function getAffectedRows()
    {
        if ( $this->statement != null )
            return $this->statement->affected_rows;
        return $this->connection->affected_rows;
    }

    public function getInsertId( $table, $column )
    {
        return $this->connection->insert_id;
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
                return "CAST( $expression AS datetime )";
            default:
                throw new System_Db_Exception( 'Invalid type' );
        }
    }

    public function checkTableExists( $table )
    {
        $query = "SHOW TABLES LIKE '$table'";
        $rs = $this->connection->query( $query );
        $rows = $rs->num_rows;
        $rs->close();
        return $rows > 0;
    }

    public function getParameter( $key )
    {
        switch ( $key ) {
            case 'server':
                return 'MySQL';
            case 'version':
                return $this->connection->server_info ;
            case 'have_innodb':
                return $this->checkEngineEnabled( 'InnoDB' );
            default:
                return null;
        }
    }

    private function checkEngineEnabled( $engine )
    {
        $result = false;

        if ( $rs = $this->connection->query( 'SHOW ENGINES' ) ) {
            while ( $row = $rs->fetch_assoc() ) {
                if ( $row[ 'Engine' ] == $engine ) {
                    if ( $row[ 'Support' ] == 'YES' || $row[ 'Support' ] == 'DEFAULT' )
                        $result = true;
                    break;
                }
            }

            $rs->close();
        }

        return $result;
    }

    public function beginTransaction( $level, $table )
    {
        switch ( $level ) {
            case System_Db_Transaction::ReadUncommitted:
                $isoLevel = 'READ UNCOMMITTED';
                break;
            case System_Db_Transaction::ReadCommitted:
                $isoLevel = 'READ COMMITTED';
                break;
            case System_Db_Transaction::RepeatableRead:
                $isoLevel = 'REPEATABLE READ';
                break;
            case System_Db_Transaction::Serializable:
                $isoLevel = 'SERIALIZABLE';
                break;
            default:
                throw new System_Db_Exception( 'Unsupported isolation level' );
        }

        if ( !$this->connection->query( "SET TRANSACTION ISOLATION LEVEL $isoLevel" ) )
            $this->handleError( $this->connection );

        if ( !$this->connection->autocommit( false ) )
            $this->handleError( $this->connection );
    }

    public function endTransaction( $commit )
    {
        if ( $commit )
            $result = $this->connection->commit();
        else
            $result = $this->connection->rollback();

        if ( !$result )
            $this->handleError( $this->connection );

        if ( !$this->connection->autocommit( true ) )
            $this->handleError( $this->connection );
    }

    private function handleError( $object )
    {
        $errno = $object->errno;

        if ( $errno == 1213 )
            throw new System_Api_Error( System_Api_Error::TransactionDeadlock, new System_Db_Exception( $object->error ) );
        if ( $errno == 1451 || $errno == 1452 )
            throw new System_Api_Error( System_Api_Error::ConstraintConflict, new System_Db_Exception( $object->error ) );

        throw new System_Db_Exception( $object->error );
    }
}
