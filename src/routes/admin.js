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

export default function makeAdminRoutes( ajax, store ) {
  return function adminRoutes( route ) {
    route( 'ManageProjects', '/admin/projects', () => {
      return ajax.post( '/server/api/projects/list.php' ).then( ( { projects } ) => {
        return {
          form: 'projects/ManageProjects',
          projects
        };
      } );
    } );

    route( 'AddProject', '/admin/projects/add', () => {
      if ( store.state.global.userAccess != Access.AdministratorAccess )
        return Promise.reject( makeError( ErrorCode.AccessDenied ) );
      return Promise.resolve( {
        form: 'projects/EditProject',
        mode: 'add',
        initialFormat: store.state.global.settings.defaultFormat
      } );
    } );

    route( 'ProjectDetails', '/admin/projects/:projectId', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, description: true, folders: true, html: true } ).then( ( { details, description, folders } ) => {
        return {
          form: 'projects/ProjectDetails',
          projectId,
          name: details.name,
          access: details.access,
          description,
          folders
        };
      } );
    } );

    route( 'RenameProject', '/admin/projects/:projectId/rename', ( { projectId } ) => {
      if ( store.state.global.userAccess != Access.AdministratorAccess )
        return Promise.reject( makeError( ErrorCode.AccessDenied ) );
      return ajax.post( '/server/api/projects/load.php', { projectId } ).then( ( { details } ) => {
        return {
          form: 'projects/EditProject',
          mode: 'rename',
          projectId,
          initialName: details.name
        };
      } );
    } );

    route( 'ArchiveProject', '/admin/projects/:projectId/archive', ( { projectId } ) => {
      if ( store.state.global.userAccess != Access.AdministratorAccess )
        return Promise.reject( makeError( ErrorCode.AccessDenied ) );
      return ajax.post( '/server/api/projects/load.php', { projectId } ).then( ( { details } ) => {
        return {
          form: 'projects/DeleteProject',
          size: 'small',
          mode: 'archive',
          projectId,
          name: details.name
        };
      } );
    } );

    route( 'DeleteProject', '/admin/projects/:projectId/delete', ( { projectId } ) => {
      if ( store.state.global.userAccess != Access.AdministratorAccess )
        return Promise.reject( makeError( ErrorCode.AccessDenied ) );
      return ajax.post( '/server/api/projects/load.php', { projectId, folders: true } ).then( ( { details, folders } ) => {
        return {
          form: 'projects/DeleteProject',
          size: 'small',
          mode: 'delete',
          projectId,
          name: details.name,
          folders
        };
      } );
    } );

    route( 'AddProjectDescription', '/admin/projects/:projectId/description/add', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, description: true, access: 'admin' } ).then( ( { details, description } ) => {
        if ( description != null )
          return Promise.reject( makeError( ErrorCode.DescriptionAlreadyExists ) );
        return {
          form: 'projects/EditProjectDescription',
          mode: 'add',
          projectId,
          projectName: details.name,
          initialFormat: store.state.global.settings.defaultFormat
        };
      } );
    } );

    route( 'EditProjectDescription', '/admin/projects/:projectId/description/edit', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, description: true, access: 'admin' } ).then( ( { details, description } ) => {
        if ( description == null )
          return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
        return {
          form: 'projects/EditProjectDescription',
          mode: 'edit',
          projectId,
          projectName: details.name,
          initialDescription: description.text,
          initialFormat: description.format
        };
      } );
    } );

    route( 'DeleteProjectDescription', '/admin/projects/:projectId/description/delete', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, description: true, access: 'admin' } ).then( ( { details, description } ) => {
        if ( description == null )
          return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
        return {
          form: 'projects/DeleteProjectDescription',
          size: 'small',
          projectId,
          projectName: details.name
        };
      } );
    } );

    route( 'AddFolder', '/admin/projects/:projectId/folders/add', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, access: 'admin' } ).then( ( { details } ) => {
        return {
          form: 'projects/EditFolder',
          mode: 'add',
          projectId,
          projectName: details.name
        };
      } );
    } );

    route( 'RenameFolder', '/admin/projects/:projectId/folders/:folderId/rename', ( { projectId, folderId } ) => {
      return ajax.post( '/server/api/projects/folders/load.php', { projectId, folderId, access: 'admin' } ).then( ( { name } ) => {
        return {
          form: 'projects/EditFolder',
          mode: 'rename',
          projectId,
          folderId,
          initialName: name
        };
      } );
    } );

    route( 'MoveFolder', '/admin/projects/:projectId/folders/:folderId/move', ( { projectId, folderId } ) => {
      return ajax.post( '/server/api/projects/folders/load.php', { projectId, folderId, access: 'admin' } ).then( ( { name } ) => {
        return {
          form: 'projects/MoveFolder',
          initialProjectId: projectId,
          folderId,
          name
        };
      } );
    } );

    route( 'DeleteFolder', '/admin/projects/:projectId/folders/:folderId/delete', ( { projectId, folderId } ) => {
      return ajax.post( '/server/api/projects/folders/load.php', { projectId, folderId, access: 'admin' } ).then( ( { name, empty } ) => {
        return {
          form: 'projects/DeleteFolder',
          projectId,
          folderId,
          name,
          empty
        };
      } );
    } );

    route( 'ProjectPermissions', '/admin/projects/:projectId/permissions', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, members: true, access: 'admin' } ).then( ( { details, members } ) => {
        return {
          form: 'projects/ProjectPermissions',
          projectId,
          name: details.name,
          public: details.public,
          members
        };
      } );
    } );

    route( 'EditProjectAccess', '/admin/projects/:projectId/permissions/edit', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, access: 'admin' } ).then( ( { details } ) => {
        return {
          form: 'projects/EditProjectAccess',
          projectId,
          name: details.name,
          initialPublic: details.public
        };
      } );
    } );

    route( 'AddMembers', '/admin/projects/:projectId/members/add', ( { projectId } ) => {
      return ajax.post( '/server/api/projects/load.php', { projectId, members: true, access: 'admin' } ).then( ( { details, members } ) => {
        return {
          form: 'projects/EditMember',
          mode: 'add',
          projectId,
          projectName: details.name,
          initialAccess: Access.NormalAccess,
          members
        };
      } );
    } );

    route( 'EditMember', '/admin/projects/:projectId/members/:userId/edit', ( { projectId, userId } ) => {
      return ajax.post( '/server/api/projects/members/load.php', { projectId, userId } ).then( ( { projectName, userName, access } ) => {
        return {
          form: 'projects/EditMember',
          mode: 'edit',
          projectId,
          userId,
          projectName,
          userName,
          initialAccess: access
        };
      } );
    } );

    route( 'RemoveMember', '/admin/projects/:projectId/members/:userId/remove', ( { projectId, userId } ) => {
      return ajax.post( '/server/api/projects/members/load.php', { projectId, userId } ).then( ( { projectName, userName } ) => {
        return {
          form: 'projects/RemoveMember',
          size: 'small',
          projectId,
          userId,
          projectName,
          userName
        };
      } );
    } );
  }
}

function makeError( errorCode ) {
  const error = new Error( 'Route error: ' + errorCode );
  error.reason = 'APIError';
  error.errorCode = errorCode;
  return error;
}
