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
* Column identifiers used by the issues grid.
*
* @see System_Api_QueryGenerator
*/
class System_Api_Column
{
    const Name = 0;
    const ID = 1;
    const CreatedDate = 2;
    const CreatedBy = 3;
    const ModifiedDate = 4;
    const ModifiedBy = 5;
    const Location = 6;

    /**
    * Value added to attribute identifier to create a user-defined column
    * identifier.
    */
    const UserDefined = 1000;
}
