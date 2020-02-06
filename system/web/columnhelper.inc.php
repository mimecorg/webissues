<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
* Class providing localized headers of system columns.
*/
class System_Web_ColumnHelper extends System_Web_Base
{
    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Return an associative array of column headers.
    */
    public function getColumnHeaders()
    {
        return array(
            System_Api_Column::ID => $this->t( 'title.ID' ),
            System_Api_Column::Name => $this->t( 'title.Name' ),
            System_Api_Column::CreatedDate => $this->t( 'title.CreatedDate' ),
            System_Api_Column::CreatedBy => $this->t( 'title.CreatedBy' ),
            System_Api_Column::ModifiedDate => $this->t( 'title.ModifiedDate' ),
            System_Api_Column::ModifiedBy => $this->t( 'title.ModifiedBy' ),
            System_Api_Column::Location => $this->t( 'title.Location' )
        );
    }

    /**
    * Create the sorting order specifier for given column and sorting order.
    */
    public static function makeOrderBy( $column, $sort )
    {
        if ( $sort == System_Const::Ascending )
            $order = ' ASC';
        else if ( $sort == System_Const::Descending )
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
