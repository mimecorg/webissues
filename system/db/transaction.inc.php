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
* Class representing a database transaction.
*/
class System_Db_Transaction
{
    const ReadUncommitted = 1;
    const ReadCommitted = 2;
    const RepeatableRead = 3;
    const Serializable = 4;

    private $connection;

    /**
    * Constructor.
    */
    public function __construct( $connection )
    {
        $this->connection = $connection;
    }

    public function commit()
    {
        if ( $this->connection != null ) {
            $this->connection->endTransaction( true );
            $this->connection = null;
        }
    }

    public function rollback()
    {
        if ( $this->connection != null ) {
            $this->connection->endTransaction( false );
            $this->connection = null;
        }
    }
}
