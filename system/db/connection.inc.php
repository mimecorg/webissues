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
* Database abstration layer.
*
* This class serves as a front-end for the database abstraction layer
* and supports various database engines. It provides basic functions for
* executing SQL queries with safe escaping of input arguments and paging
* independent of used SQL dialect. It also logs executed queries to the
* debugging log if debugging level is greater than or equal to DEBUG_SQL.
*
* All queries should be written using standard SQL syntax which should be
* understood by all supported databases. Table names should be enclosed in
* curly brackets so that prexix can be automatically appended where needed.
*
* Variables should be passed as arguments to avoid SQL injection attacks.
* The following placeholders are substituted with argument values:
*  - %%d - integer value
*  - %%f - floating point number
*  - %%s - utf-8 encoded string
*  - %%t - date and time
*  - %%b - System_Core_Attachment object containing binary data
*
* An optional argument position can be inserted after the %, for example
* %%2d. When not present, consecutive arguments are taken.
*
* An optional ? can be inserted after the placeholder. When the value is null,
* 0 or '', it will be replaced with NULL and the = before the placeholder will
* be replaced with IS. When ! is used instead of ?, then = is not replaced.
*
* If the % is doubled, an array of arguments should be passed. The elements
* will be separated with commas.
*
* All methods have two versions. The basic version accepts substitution
* arguments after the regular arguments, for example:
* @code
* $connection->execute( 'UPDATE {foo} SET name = %s WHERE id = %d', 'abc', 123 );
* @endcode
*
* The version with 'Args' suffix takes substitution arguments as an array.
*
* All methods throw System_Db_Exception in case of an error.
*
* The backend engine must implement a System_Db_IEngine interface.
* The following back-end engines are currently supported:
*  - mysqli
*  - pgsql
*  - mssql
*
* The connection is automatically initialized by System_Core_Application based
* on the site configuration file created during the installation.
*
* An instance of this class is accessible through the System_Core_Application
* object and as a property of classes inheriting System_Api_Base.
*/
class System_Db_Connection
{
    private $engine = null;
    private $opened = false;

    private $engineName = null;

    private $prefix = '';

    private $transaction = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Return @c true if the connection was succesfully opened.
    */
    public function isOpened()
    {
        return $this->opened;
    }

    /**
    * Load a back-end engine.
    * @param $engineName Name of the engine to load.
    */
    public function loadEngine( $engineName )
    {
        $this->engineName = $engineName;

        switch ( $engineName ) {
            case 'mysqli':
                $this->engine = new System_Db_Mysqli_Engine();
                break;
            case 'pgsql':
                $this->engine = new System_Db_Pgsql_Engine();
                break;
            case 'mssql':
                $this->engine = new System_Db_Mssql_Engine();
                break;
            default:
                throw new System_Db_Exception( "Unknown database engine '$engineName'" );
        }
    }

    /**
    * Create an instance of the System_Db_SchemaGenerator for this database.
    */
    public function getSchemaGenerator()
    {
        switch ( $this->engineName ) {
            case 'mysqli':
                return new System_Db_Mysqli_SchemaGenerator( $this );
            case 'pgsql':
                return new System_Db_Pgsql_SchemaGenerator( $this );
            case 'mssql':
                return new System_Db_Mssql_SchemaGenerator( $this );
            default:
                throw new System_Db_Exception( "Unknown database schema engine" );
        }
    }

    /**
    * Connect to the specified database server. The detailed meaning,
    * syntax and support for the individual parameters depends on the
    * used back-end engine.
    * @param $host Name of the database server.
    * @param $database Name of the database.
    * @param $user Login name used for authentication.
    * @param $password Password used for authentication.
    */
    public function open( $host, $database, $user, $password )
    {
        $this->engine->open( $host, $database, $user, $password );
        $this->opened = true;
    }

    /**
    * Close the connection to the database server.
    */
    public function close()
    {
        if ( $this->opened ) {
            $this->engine->close();
            $this->opened = false;
        }
    }

    /**
    * Set a prefix prepended to all table names.
    */
    public function setPrefix( $prefix )
    {
        $this->prefix = $prefix;
    }

    /**
    * Execute a statement which returns no result. This method can be used
    * to execute statements like INSERT, UPDATE, DELETE, etc.
    * @param $query The query to execute.
    */
    public function execute( $query )
    {
        $args = func_get_args();
        array_shift( $args );

        $this->executeArgs( $query, $args );
    }

    /**
    * Execute the query without additional processing.
    */
    public function executeRaw( $query )
    {
        $this->engine->execute( $query, array() );
    }

    /**
    * Execute a query which returns no result. This method works like
    * execute() but takes substitution arguments as an array.
    * @param $query The query to execute.
    * @param $args The substitution arguments.
    */
    public function executeArgs( $query, $args )
    {
        $params = array();
        $realQuery = $this->buildQuery( $query, $args, $params );

        $debug = System_Core_Application::getInstance()->getDebug();
        if ( $debug->checkLevel( DEBUG_SQL ) ) {
            $debugQuery = $this->buildDebugQuery( $query, $args );
            $debug->write( 'SQL Execute: ', $debugQuery, "\n" );
            $startTime = microtime( true );
        }

        $this->engine->execute( $realQuery, $params );

        if ( $debug->checkLevel( DEBUG_SQL ) ) {
            $time = ( microtime( true ) - $startTime ) * 1000;
            $debug->write( sprintf( "Query execution time: %.1f ms\n", $time ) );
            $debug->write( sprintf( "Affected rows: %d\n", $this->engine->getAffectedRows() ) );
        }
    }

    /**
    * Execute a SELECT query which returns a record set.
    * @param $query The query to execute.
    * @return An object implementing System_Db_IResult.
    */
    public function query( $query )
    {
        $args = func_get_args();
        array_shift( $args );

        return $this->queryArgs( $query, $args );
    }

    /**
    * Execute a SELECT query which returns a record set. This method works like
    * query() but takes substitution arguments as an array.
    * @param $query The query to execute.
    * @param $args The substitution arguments.
    * @return An object implementing System_Db_IResult.
    */
    public function queryArgs( $query, $args )
    {
        $params = array();
        $realQuery = $this->buildQuery( $query, $args, $params );

        $debug = System_Core_Application::getInstance()->getDebug();
        if ( $debug->checkLevel( DEBUG_SQL ) ) {
            $debugQuery = $this->buildDebugQuery( $query, $args );
            $debug->write( 'SQL Query: ', $debugQuery, "\n" );
            $startTime = microtime( true );
        }

        $result = $this->engine->query( $realQuery, $params );

        if ( $debug->checkLevel( DEBUG_SQL ) ) {
            $time = ( microtime( true ) - $startTime ) * 1000;
            $debug->write( sprintf( "Query execution time: %.1f ms\n", $time ) );
            $debug->write( sprintf( "Returned rows: %d\n", $result->getRowCount() ) );
        }

        return $result;
    }

    /**
    * Execute a SELECT query which returns a set of rows.
    * @param $query The query to execute.
    * @return An array of associative arrays representing rows.
    */
    public function queryTable( $query )
    {
        $args = func_get_args();
        array_shift( $args );

        return $this->queryTableArgs( $query, $args );
    }

    /**
    * Execute a SELECT query which returns a set of rows. This method works
    * like queryTable() but takes substitution arguments as an array.
    * @param $query The query to execute.
    * @param $args The substitution arguments.
    * @return An array of associative arrays representing rows.
    */
    public function queryTableArgs( $query, $args )
    {
        $rs = $this->queryArgs( $query, $args );

        $table = array();
        while ( $row = $rs->fetch() )
            $table[] = $row;

        return $table;
    }

    /**
    * Execute a SELECT query which returns a single row.
    * @param $query The query to execute.
    * @return An associative array representing a row or @c false if query
    * returns no rows.
    */
    public function queryRow( $query )
    {
        $args = func_get_args();
        array_shift( $args );

        return $this->queryRowArgs( $query, $args );
    }

    /**
    * Execute a SELECT query which returns a single row. This method works
    * like queryRow() but takes substitution arguments as an array.
    * @param $query The query to execute.
    * @param $args The substitution arguments.
    * @return An associative array representing a row or @c false if query
    * returns no rows.
    */
    public function queryRowArgs( $query, $args )
    {
        $rs = $this->queryArgs( $query, $args );

        $row = $rs->fetch();

        if ( $row )
            $rs->close();

        return $row;
    }

    /**
    * Execute a SELECT query which returns a single value.
    * @param $query The query to execute.
    * @return The scalar value returned by the query or @c false if query
    * returns no rows.
    */
    public function queryScalar( $query )
    {
        $args = func_get_args();
        array_shift( $args );

        return $this->queryScalarArgs( $query, $args );
    }

    /**
    * Execute a SELECT query which returns a single value. This method works
    * like queryScalar() but takes substitution arguments as an array.
    * @param $query The query to execute.
    * @param $args The substitution arguments.
    * @return The scalar value returned by the query or @c false if query
    * returns no rows.
    */
    public function queryScalarArgs( $query, $args )
    {
        $rs = $this->queryArgs( $query, $args );

        $row = $rs->fetch();

        if ( $row ) {
            $rs->close();
            return reset( $row );
        }

        return false;
    }

    /**
    * Execute a SELECT query with sorting and paging.
    * @param $query The query to execute.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing rows.
    */
    public function queryPage( $query, $orderBy, $limit, $offset )
    {
        $args = func_get_args();
        $args = array_slice( $args, 4 );

        return $this->queryPageArgs( $query, $orderBy, $limit, $offset, $args );
    }

    /**
    * Execute a SELECT query with sorting and paging. This method works
    * like queryPage() but takes substitution arguments as an array.
    * @param $query The query to execute.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @param $args The substitution arguments.
    * @return An array of associative arrays representing rows.
    */
    public function queryPageArgs( $query, $orderBy, $limit, $offset, $args )
    {
        $query = $this->engine->getPagedQuery( $query, $orderBy, $limit, $offset );

        $rs = $this->queryArgs( $query, $args );

        $page = array();
        while ( $row = $rs->fetch() )
            $page[] = $row;

        return $page;
    }

    /**
    * Create a System_Core_Attachment from a binary result field value.
    * @param $data Database-specific value of a binary field.
    * @param $size Size of data in bytes.
    * @param $fileName The original file name.
    * @return The System_Core_Attachment object.
    */
    public function createAttachment( $data, $size, $fileName )
    {
        return $this->engine->createAttachment( $data, $size, $fileName );
    }

    /**
    * Return the primary key of the last inserted row. This function only works
    * immediately after executing the INSERT query.
    * @param $table Name of the table where the row was inserted (without brackets).
    * @param $column Name of the primary key column.
    * @return The value of the primary key for the last inserted row.
    */
    public function getInsertId( $table, $column )
    {
        return $this->engine->getInsertId( $this->prefix . $table, $column );
    }

    /**
    * Cast the expression to the given type.
    * @param $expression An SQL expression.
    * @param $type Letter specifying the type ('d', 'f', 's' or 't').
    * @return The SQL expression casted to the given type.
    */
    public function castExpression( $expression, $type )
    {
        return $this->engine->castExpression( $expression, $type );
    }

    /**
    * Check if the table with given name exists.
    * @param $table Table name without brackets; the prefix is automatically
    * appended.
    * @return @c true if the table exists and @c false otherwise.
    */
    public function checkTableExists( $table )
    {
        return $this->engine->checkTableExists( $this->prefix . $table );
    }

    /**
    * Get an engine-dependent parameter of the connection.
    */
    public function getParameter( $key )
    {
        return $this->engine->getParameter( $key );
    }

    /**
    * Begin a transaction with given isolation level.
    * @param $level Transaction isolation level.
    * @param $table Name of the table to lock (for PostgreSQL).
    */
    public function beginTransaction( $level = null, $table = null )
    {
        if ( $this->transaction != null )
            throw new System_Db_Exception( 'Nested transactions are not supported' );

        if ( $level == null )
            $level = System_Db_Transaction::ReadCommitted;

        if ( $table != null )
            $table = $this->prefix . $table;

        $debug = System_Core_Application::getInstance()->getDebug();
        if ( $debug->checkLevel( DEBUG_SQL ) )
            $debug->write( "Begin Transaction\n" );

        $this->engine->beginTransaction( $level, $table );
        $this->transaction = new System_Db_Transaction( $this );

        return $this->transaction;
    }

    /**
    * Return the current transaction.
    */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
    * @internal
    */
    public function endTransaction( $commit = false )
    {
        $debug = System_Core_Application::getInstance()->getDebug();
        if ( $debug->checkLevel( DEBUG_SQL ) )
            $debug->write( $commit ? "Commit Transaction\n" : "Rollback Transaction\n" );

        $this->engine->endTransaction( $commit );
        $this->transaction = null;
    }

    private function buildQuery( $query, $args, &$params )
    {
        $query = str_replace( '{', $this->prefix, $query );
        $query = str_replace( '}', '', $query );

        $collation = $this->engine->getLocaleCollation();
        if ( $collation != '' )
            $collation = ' COLLATE ' . $collation;
        $query = str_replace( ' COLLATE LOCALE', $collation, $query );

        // don't use the regular expression if not necessary
        if ( strpos( $query, '%' ) === false )
            return $query;

        $parts = preg_split( '/%(%?)(\d*)([dfsbt])([?!]?)/', $query, -1, PREG_SPLIT_DELIM_CAPTURE );

        $result = array( $parts[ 0 ] );
        $pos = 1;

        for ( $i = 1; $i < count( $parts ); $i += 5 ) {
            if ( $parts[ $i + 1 ] === '' )
                $index = $pos++;
            else
                $index = (int)$parts[ $i + 1 ];

            if ( $index > 0 && $index <= count( $args ) )
                $arg = $args[ $index - 1 ];
            else
                throw new System_Db_Exception( "Invalid argument offset '$index' in query" );

            $type = $parts[ $i + 2 ];

            if ( $parts[ $i + 3 ] == '?' && $arg == null ) {
                $top = array_pop( $result );
                if ( substr( $top, -3, 3 ) == ' = ' )
                    $top = substr( $top, 0, -3 ) . ' IS ';
                $result[] = $top;
                $result[] = 'NULL';
            } else if ( $parts[ $i + 3 ] == '!' && $arg == null ) {
                $result[] = 'NULL';
            } else if ( $parts[ $i ] == '%' ) {
                $result[] = $this->buildList( $arg, $type, $params );
            } else {
                $result[] = $this->engine->escapeArgument( $arg, $type, $params );
            }

            $result[] = $parts[ $i + 4 ];
        }

        return implode( '', $result );
    }

    private function buildList( $list, $type, &$params )
    {
        $result = array();
        foreach ( $list as $item )
            $result[] = $this->engine->escapeArgument( $item, $type, $params );
        return implode( ', ', $result );
    }

    private function buildDebugQuery( $query, $args )
    {
        foreach ( $args as &$value ) {
            if ( is_string( $value ) ) {
                $value = str_replace( "\n", ' ', $value );
                if ( mb_strlen( $value ) > 15 )
                    $value = mb_substr( $value, 0, 15 ) . '...';
            }
        }
        $params = array();
        return $this->buildQuery( $query, $args, $params );
    }
}
