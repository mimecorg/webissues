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
* Implementation of a record set for PostgreSQL.
*/
class System_Db_Pgsql_Result implements System_Db_IResult
{
    private $result = null;

    /**
    * Constructor.
    */
    public function __construct( $result )
    {
        $this->result = $result;
    }

    public function fetch()
    {
        if ( $row = pg_fetch_assoc( $this->result ) )
            return $row;
        return null;
    }

    public function close()
    {
        pg_free_result( $this->result );
        $this->result = null;
    }

    public function getRowCount()
    {
        return pg_num_rows( $this->result );
    }
}
