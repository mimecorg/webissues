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
* Implementation of a result for MySQL using a prepared statement.
*
* This class is only used when the query contains long string parameters
* for maximum performance.
*/
class System_Db_Mysqli_Statement implements System_Db_IResult
{
    private $statement = null;
    private $buffer = null;

    /**
    * Constructor.
    */
    public function __construct( $statement )
    {
        $fields = $statement->result_metadata();
        if ( $fields == null )
            throw new System_Db_Exception( 'Query produced no result' );

        $this->statement = $statement;
        $this->buffer = array();

        $statement->store_result();

        $args = array( $statement );
        while( $field = $fields->fetch_field() )
            $args[] =& $this->buffer[ $field->name ];

        call_user_func_array( 'mysqli_stmt_bind_result', $args );

        $fields->free();
    }

    public function fetch()
    {
        if ( $this->statement->fetch() ) {
            $result = array();
            foreach ( $this->buffer as $key => $value )
                $result[ $key ] = $value;
            return $result;
        }
        return null;
    }

    public function close()
    {
        $this->statement->free_result();
        $this->statement = null;
    }

    public function getRowCount()
    {
        return $this->statement->num_rows;
    }
}
