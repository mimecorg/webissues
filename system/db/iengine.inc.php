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
* Interface for back-end engines for System_Db_Connection.
*/
interface System_Db_IEngine
{
    /**
    * Connect to the specified database server.
    * @param $host Name of the database server.
    * @param $database Name of the database.
    * @param $user Login name used for authentication.
    * @param $password Password used for authentication.
    */
    public function open( $host, $database, $user, $password );

    /**
    * Close the connection to the database server.
    */
    public function close();

    /**
    * Execute a statement which returns no result.
    * @param $query The query to execute.
    * @param $params Additional parameters as returned by escapeArgument().
    */
    public function execute( $query, $params );

    /**
    * Execute a query which returns a record set.
    * @param $query The query to execute.
    * @param $params Additional parameters as returned by escapeArgument().
    * @return An object implementing System_Db_IResult.
    */
    public function query( $query, $params );

    /**
    * Insert an argument into the query. This method should apply appropriate
    * escaping of the value. It can also return a parameter placeholder and
    * append the value to the array of parameters is the database supports
    * parametrized queries.
    * @param $arg Value of the argument to substitute.
    * @param $type Type of the argument: 'd', 'f', 's' or 'b'.
    * @param $params An array which can be used to store parameters.
    * @return The escaped value to be inserted into the query.
    */
    public function escapeArgument( $arg, $type, &$params );

    /**
    * Apply sorting and paging to the SELECT query.
    * @param $query The query to modify.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return The modified query.
    */
    public function getPagedQuery( $query, $orderBy, $limit, $offset );

    /**
    * Return the name of collation used for case-insensitive ordering.
    */
    public function getLocaleCollation();

    /**
    * Create a System_Core_Attachment from a binary result field value.
    * @param $data Database-specific value of a binary field.
    * @param $size Size of data in bytes.
    * @param $fileName The original file name.
    * @return The System_Core_Attachment object.
    */
    public function createAttachment( $data, $size, $fileName );

    /**
    * Return the number of rows affected by the last statement.
    * This method is used for debugging purposes only.
    */
    public function getAffectedRows();

    /**
    * Return the primary key of the last inserted row.
    * @param $table Name of the table where the row was inserted (with prefix).
    * @param $column Name of the primary key column.
    * @return The value of the primary key for the last inserted row.
    */
    public function getInsertId( $table, $column );

    /**
    * Cast the expression to the given type.
    * @param $expression An SQL expression.
    * @param $type Letter specifying the type ('d', 'f', 's' or 't').
    * @return The SQL expression casted to the given type.
    */
    public function castExpression( $expression, $type );

    /**
    * Check if the table with given name exists.
    * @param $table Table name with prefix.
    * @return @c true if the table exists and @c false otherwise.
    */
    public function checkTableExists( $table );

    /**
    * Get an engine-dependent parameter of the connection.
    */
    public function getParameter( $key );

    /**
    * Begin a transaction with given isolation level.
    */
    public function beginTransaction( $level, $table );

    /**
    * Commit or rollback a transaction.
    */
    public function endTransaction( $commit );
}
