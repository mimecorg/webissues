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

import { Column } from '@/constants'

export function getColumnName( column ) {
  switch ( column ) {
    case Column.ID:
      return this.$t( 'title.ID' );
    case Column.Name:
      return this.$t( 'title.Name' );
    case Column.CreatedBy:
      return this.$t( 'title.CreatedBy' );
    case Column.CreatedDate:
      return this.$t( 'title.CreatedDate' );
    case Column.ModifiedBy:
      return this.$t( 'title.ModifiedBy' );
    case Column.ModifiedDate:
      return this.$t( 'title.ModifiedDate' );
    default:
      if ( column > Column.UserDefined ) {
        const attribute = this.attributes.find( a => a.id == column - Column.UserDefined );
        if ( attribute != null )
          return attribute.name;
      }
      return this.$t( 'text.UnknownColumn' );
  }
}
