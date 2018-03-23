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

export default function staticRoutes( route ) {
  route( 'Home', '' );
  route( 'List', '/types/:typeId' );
  route( 'ListView', '/views/:viewId' );
  route( 'ListProject', '/types/:typeId/projects/:projectId' );
  route( 'ListViewProject', '/views/:viewId/projects/:projectId' );
  route( 'ListFolder', '/folders/:folderId' );
  route( 'ListViewFolder', '/views/:viewId/folders/:folderId' );
}
