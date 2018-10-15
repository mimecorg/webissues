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

import { Access, ErrorCode } from '@/constants'
import { makeError } from '@/utils/errors'

export default function routeUsers( route, ajax, store ) {
  route( 'ManageUsers', '/admin/users', () => {
    return ajax.post( '/server/api/users/list.php' ).then( ( { users } ) => {
      return {
        form: 'users/ManageUsers',
        size: 'large',
        users
      };
    } );
  } );

  route( 'AddUser', '/admin/users/add', () => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return Promise.resolve( { form: 'users/EditUser', mode: 'add' } );
  } );

  route( 'UserDetails', '/admin/users/:userId', ( { userId } ) => {
    return ajax.post( '/server/api/users/load.php', { userId, projects: true } ).then( ( { name, details, projects } ) => {
      return {
        form: 'users/UserDetails',
        userId,
        name,
        details,
        userProjects: projects
      };
    } );
  } );

  route( 'EditUser', '/admin/users/:userId/edit', ( { userId } ) => {
    return ajax.post( '/server/api/users/load.php', { userId } ).then( ( { name, details } ) => {
      return {
        form: 'users/EditUser',
        mode: 'edit',
        userId,
        initialName: name,
        initialLogin: details.login,
        initialEmail: details.email,
        initialLanguage: details.language,
      };
    } );
  } );

  route( 'EditUserAccess', '/admin/users/:userId/permissions/edit', ( { userId } ) => {
    return ajax.post( '/server/api/users/load.php', { userId } ).then( ( { name, details } ) => {
      return {
        form: 'users/EditUserAccess',
        size: 'small',
        userId,
        name,
        initialAccess: details.access
      };
    } );
  } );

  route( 'AddUserProjects', '/admin/users/:userId/projects/add', ( { userId } ) => {
    return ajax.post( '/server/api/users/load.php', { userId, projects: true } ).then( ( { name, projects } ) => {
      return {
        form: 'users/EditUserProject',
        mode: 'add',
        userId,
        userName: name,
        initialAccess: Access.NormalAccess,
        userProjects: projects
      };
    } );
  } );

  route( 'EditUserProject', '/admin/users/:userId/projects/:projectId/edit', ( { userId, projectId } ) => {
    return ajax.post( '/server/api/users/projects/load.php', { userId, projectId } ).then( ( { userName, projectName, access } ) => {
      return {
        form: 'users/EditUserProject',
        size: 'small',
        mode: 'edit',
        userId,
        projectId,
        userName,
        projectName,
        initialAccess: access
      };
    } );
  } );

  route( 'RemoveUserProject', '/admin/users/:userId/projects/:projectId/remove', ( { userId, projectId } ) => {
    return ajax.post( '/server/api/users/projects/load.php', { userId, projectId } ).then( ( { userName, projectName } ) => {
      return {
        form: 'users/RemoveUserProject',
        size: 'small',
        userId,
        projectId,
        userName,
        projectName
      };
    } );
  } );
}
