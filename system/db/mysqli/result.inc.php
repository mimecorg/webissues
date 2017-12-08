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
* Implementation of a record set for MySQL.
*/
class System_Db_Mysqli_Result implements System_Db_IResult
{
    private $recordset = null;

    /**
    * Constructor.
    */
    public function __construct( $recordset )
    {
        if ( !is_object( $recordset ) )
            throw new System_Db_Exception( 'Query produced no result' );

        $this->recordset = $recordset;
    }

    public function fetch()
    {
        if ( $row = $this->recordset->fetch_assoc() )
            return $row;
        return null;
    }

    public function close()
    {
        $this->recordset->free_result();
        $this->recordset = null;
    }

    public function getRowCount()
    {
        return $this->recordset->num_rows;
    }
}
