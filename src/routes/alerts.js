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

import { Access } from '@/constants'

export default function routeAlerts( route, ajax, store ) {
  route( 'ManageAlerts', '/alerts', () => {
    const isAdministrator = store.state.global.userAccess == Access.AdministratorAccess;
    return ajax.post( '/alerts/list.php', { publicAlerts: isAdministrator, personalAlerts: true } ).then( ( { publicAlerts, personalAlerts } ) => {
      return {
        form: 'alerts/ManageAlerts',
        publicAlerts,
        personalAlerts
      };
    } );
  } );

  route( 'AddPublicAlert', '/alerts/public/add', () => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return Promise.resolve( {
      form: 'alerts/EditAlert',
      mode: 'add',
      isPublic: true
    } );
  } );

  route( 'AddPersonalAlert', '/alerts/personal/add', () => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return Promise.resolve( {
      form: 'alerts/EditAlert',
      mode: 'add',
      isPublic: false
    } );
  } );

  route( 'EditAlert', '/alerts/:alertId/edit', ( { alertId } ) => {
    return ajax.post( '/alerts/load.php', { alertId, details: true } ).then( ( { isPublic, view, location, details } ) => {
      return {
        form: 'alerts/EditAlert',
        mode: 'edit',
        alertId,
        isPublic,
        view,
        location,
        initialAlert: details
      };
    } );
  } );
}
