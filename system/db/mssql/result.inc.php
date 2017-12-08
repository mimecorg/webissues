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
* Implementation of a record set for Microsoft SQL Server.
*/
class System_Db_Mssql_Result implements System_Db_IResult
{
    private $recordset = null;

    private $cache = null;
    private $count = 0;

    /**
    * Constructor.
    */
    public function __construct( $rs )
    {
        $this->recordset = $rs;
    }

    public function fetch()
    {
        if ( $this->cache != null ) {
            if ( list( $key, $row ) = each( $this->cache ) )
                return $row;
            return false;
        }

        if ( $this->recordset == null )
            return false;

        if ( $this->recordset->EOF ) {
            $this->close();
            return false;
        }

        $row = array();
        $count = $this->recordset->Fields->Count;

        for( $i = 0; $i < $count; $i++ ) {
            $field = $this->recordset->Fields[ $i ];
            $row[ $field->Name ] = $field->Value;
        }

        $this->recordset->MoveNext();
        $this->count++;

        return $row;
    }

    public function close()
    {
        if ( $this->recordset != null ) {
            $this->recordset->Close();
            $this->recordset = null;
        }
    }

    public function getRowCount()
    {
        if ( $this->recordset != null && !$this->recordset->EOF ) {
            // ADORecordset uses a forward cursor by default so row count is unknown until all rows are fetched
            $cache = array();
            while ( $row = $this->fetch() )
                $cache[] = $row;

            $this->close();

            $this->cache = $cache;
            reset( $this->cache );
        }

        return $this->count;
    }
}
