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
  route( 'ManageUsers', '/users', () => {
    return ajax.post( '/users/list.php' ).then( ( { users } ) => {
      return {
        form: 'users/ManageUsers',
        users
      };
    } );
  } );

  route( 'AddUser', '/users/add', () => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return Promise.resolve( { form: 'users/EditUser', mode: 'add' } );
  } );

  route( 'UserDetails', '/users/:userId', ( { userId } ) => {
    return ajax.post( '/users/load.php', { userId, projects: true } ).then( ( { details, projects } ) => {
      return {
        form: 'users/UserDetails',
        userId,
        name: details.name,
        login: details.login,
        email: details.email,
        language: details.language,
        access: details.access,
        userProjects: projects
      };
    } );
  } );

  route( 'EditUser', '/users/:userId/edit', ( { userId } ) => {
    return ajax.post( '/users/load.php', { userId } ).then( ( { details } ) => {
      return {
        form: 'users/EditUser',
        mode: 'edit',
        userId,
        initialName: details.name,
        initialLogin: details.login,
        initialEmail: details.email,
        initialLanguage: details.language
      };
    } );
  } );

  route( 'EditUserAccess', '/users/:userId/permissions/edit', ( { userId } ) => {
    return ajax.post( '/users/load.php', { userId } ).then( ( { details } ) => {
      return {
        form: 'users/EditUserAccess',
        userId,
        name: details.name,
        initialAccess: details.access
      };
    } );
  } );

  route( 'AddUserProjects', '/users/:userId/projects/add', ( { userId } ) => {
    return ajax.post( '/users/load.php', { userId, projects: true } ).then( ( { details, projects } ) => {
      return {
        form: 'users/EditUserProject',
        mode: 'add',
        userId,
        userName: details.name,
        initialAccess: Access.NormalAccess,
        userProjects: projects
      };
    } );
  } );

  route( 'EditUserProject', '/users/:userId/projects/:projectId/edit', ( { userId, projectId } ) => {
    return ajax.post( '/users/projects/load.php', { userId, projectId } ).then( ( { userName, projectName, access } ) => {
      return {
        form: 'users/EditUserProject',
        mode: 'edit',
        userId,
        projectId,
        userName,
        projectName,
        initialAccess: access
      };
    } );
  } );

  route( 'RemoveUserProject', '/users/:userId/projects/:projectId/remove', ( { userId, projectId } ) => {
    return ajax.post( '/users/projects/load.php', { userId, projectId } ).then( ( { userName, projectName } ) => {
      return {
        form: 'users/RemoveUserProject',
        userId,
        projectId,
        userName,
        projectName
      };
    } );
  } );

  route( 'ChangePassword', '/users/:userId/password/change', ( { userId } ) => {
    return ajax.post( '/users/load.php', { userId } ).then( ( { details } ) => {
      return {
        form: 'users/ChangePassword',
        userId,
        name: details.name
      };
    } );
  } );

  route( 'ResetPassword', '/users/:userId/password/reset', ( { userId } ) => {
    return ajax.post( '/users/load.php', { userId } ).then( ( { details } ) => {
      return {
        form: 'users/ResetPassword',
        userId,
        name: details.name,
        email: details.email
      };
    } );
  } );

  route( 'RegistrationRequests', '/users/requests', () => {
    return ajax.post( '/users/requests/list.php' ).then( ( { requests } ) => {
      return {
        form: 'users/RegistrationRequests',
        requests
      };
    } );
  } );

  route( 'RequestDetails', '/users/requests/:requestId', ( { requestId } ) => {
    return ajax.post( '/users/requests/load.php', { requestId } ).then( ( { details } ) => {
      return {
        form: 'users/RequestDetails',
        requestId,
        name: details.name,
        login: details.login,
        email: details.email,
        date: details.date
      };
    } );
  } );

  route( 'ApproveRequest', '/users/requests/:requestId/approve', ( { requestId } ) => {
    return ajax.post( '/users/requests/load.php', { requestId } ).then( ( { details } ) => {
      return {
        form: 'users/ApproveRequest',
        requestId,
        name: details.name
      };
    } );
  } );

  route( 'RejectRequest', '/users/requests/:requestId/reject', ( { requestId } ) => {
    return ajax.post( '/users/requests/load.php', { requestId } ).then( ( { details } ) => {
      return {
        form: 'users/RejectRequest',
        requestId,
        name: details.name
      };
    } );
  } );
}
