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
            System_Api_Column::ID => $this->tr( 'ID' ),
            System_Api_Column::Name => $this->tr( 'Name' ),
            System_Api_Column::CreatedDate => $this->tr( 'Created Date' ),
            System_Api_Column::CreatedBy => $this->tr( 'Created By' ),
            System_Api_Column::ModifiedDate => $this->tr( 'Modified Date' ),
            System_Api_Column::ModifiedBy => $this->tr( 'Modified By' ),
            System_Api_Column::Location => $this->tr( 'Location' )
        );
    }
}
