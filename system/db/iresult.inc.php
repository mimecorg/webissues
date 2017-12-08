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
* Interface representing a result of a query.
*/
interface System_Db_IResult
{
    /**
    * Return the next row from the result.
    * @return An associative array representing a row or @c false if
    * there are no more rows in the result.
    */
    public function fetch();

    /**
    * Close the result and free memory used by it.
    */
    public function close();

    /**
    * Return the total number of rows in the query.
    * This method is used for debugging purposes only and may affect
    * code performance.
    */
    public function getRowCount();
}
