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

export default function routeGlobal( route, ajax, store ) {
  route( 'Home', '' );
  route( 'List', '/types/:typeId/issues' );
  route( 'ListView', '/views/:viewId/issues' );
  route( 'ListProject', '/types/:typeId/projects/:projectId/issues' );
  route( 'ListViewProject', '/views/:viewId/projects/:projectId/issues' );
  route( 'ListFolder', '/folders/:folderId/issues' );
  route( 'ListViewFolder', '/views/:viewId/folders/:folderId/issues' );

  route( 'About', '/about', () => {
    return Promise.resolve( {
      form: 'about/AboutForm',
      serverVersion: store.state.global.serverVersion
    } );
  } );

  route( 'MyAccount', '/account', () => {
    return ajax.post( '/account/load.php', { projects: true } ).then( ( { details, projects } ) => {
      return {
        form: 'users/UserDetails',
        userId: details.id,
        name: details.name,
        login: details.login,
        email: details.email,
        language: details.language,
        access: details.access,
        userProjects: projects,
        accountMode: true
      };
    } );
  } );

  route( 'EditAccount', '/account/edit', () => {
    return ajax.post( '/account/load.php' ).then( ( { details } ) => {
      return {
        form: 'users/EditUser',
        mode: 'account',
        userId: details.id,
        initialName: details.name,
        initialLogin: details.login,
        initialEmail: details.email,
        initialLanguage: details.language
      };
    } );
  } );

  route( 'AddAccountProjects', '/account/projects/add', () => {
    return ajax.post( '/users/load.php', { userId: store.state.global.userId, projects: true } ).then( ( { details, projects } ) => {
      return {
        form: 'users/EditUserProject',
        mode: 'add',
        userId: details.id,
        userName: details.name,
        initialAccess: Access.NormalAccess,
        userProjects: projects,
        accountMode: true
      };
    } );
  } );

  route( 'EditAccountProject', '/account/projects/:projectId/edit', ( { projectId } ) => {
    return ajax.post( '/users/projects/load.php', { userId: store.state.global.userId, projectId } ).then( ( { userName, projectName, access } ) => {
      return {
        form: 'users/EditUserProject',
        mode: 'edit',
        userId: store.state.global.userId,
        projectId,
        userName,
        projectName,
        initialAccess: access,
        accountMode: true
      };
    } );
  } );

  route( 'RemoveAccountProject', '/account/projects/:projectId/remove', ( { projectId } ) => {
    return ajax.post( '/users/projects/load.php', { userId: store.state.global.userId, projectId } ).then( ( { userName, projectName } ) => {
      return {
        form: 'users/RemoveUserProject',
        userId: store.state.global.userId,
        projectId,
        userName,
        projectName,
        accountMode: true
      };
    } );
  } );

  route( 'ChangeAccountPassword', '/account/password/change', () => {
    return ajax.post( '/account/load.php' ).then( ( { details } ) => {
      return {
        form: 'users/ChangePassword',
        userId: details.id,
        name: details.name,
        accountMode: true
      };
    } );
  } );

  route( 'ResetAccountPassword', '/account/password/reset', ( { userId } ) => {
    return ajax.post( '/account/load.php' ).then( ( { details } ) => {
      return {
        form: 'users/ResetPassword',
        userId: details.id,
        name: details.name,
        email: details.email,
        accountMode: true
      };
    } );
  } );
}
