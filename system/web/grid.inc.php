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
* Helper class for handling and rendering grids with sortable columns.
*
* This class provides support for the components for handling query string
* parameters and determining sorting and paging parameters to be used with
* System_Db_Connection::queryPage().
*
* It also provides support for the views for rendering grid headers with
* sortable columns and pager controls. It can also be used for handling
* stand-alone pager controls.
*
* The controller must at least pass the total number of rows and - if the
* grid has sortable columns - pass an array containing internal identifiers
* of columns which are used in the URLs and expressions which can be used
* to build the ORDER BY clause in SQL. It can then use getOffset() and
* getOrderBy() to calculate parameters for the System_Db_Connection::queryPage()
* method.
*
* Note that this is not a fully functional grid and retrieving and rendering
* data must be implemented in the component and the view.
*/
class System_Web_Grid extends System_Web_Base
{
    /**
    * Sort in ascending order.
    */
    const Ascending = 'asc';
    /**
    * Sort in descending order.
    */
    const Descending = 'desc';

    /**
    * Create the sorting order specifier for given column and sorting order.
    */
    public static function makeOrderBy( $column, $sort )
    {
        if ( $sort == self::Ascending )
            $order = ' ASC';
        else if ( $sort == self::Descending )
            $order = ' DESC';
        else
            throw new System_Core_Exception( 'Invalid sort order' );

        $parts = explode( ', ', $column );

        foreach ( $parts as &$part ) {
            if ( substr( $part, -4 ) != ' ASC' && substr( $part, -5 ) != ' DESC' )
                $part .= $order;
        }

        return implode( ', ', $parts );
    }
}
