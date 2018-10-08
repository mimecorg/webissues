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

export default function routeTypes( route, ajax, store ) {
  route( 'ManageTypes', '/admin/types', () => {
    return ajax.post( '/server/api/types/list.php' ).then( ( { types } ) => {
      return {
        form: 'types/ManageTypes',
        types
      };
    } );
  } );

  route( 'AddType', '/admin/types/add', () => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return Promise.resolve( {
      form: 'types/EditType',
      size: 'small',
      mode: 'add'
    } );
  } );

  route( 'TypeDetails', '/admin/types/:typeId', ( { typeId } ) => {
    return ajax.post( '/server/api/types/load.php', { typeId, attributes: true } ).then( ( { name, attributes } ) => {
      return {
        form: 'types/TypeDetails',
        size: 'large',
        typeId,
        name,
        attributes
      };
    } );
  } );

  route( 'RenameType', '/admin/types/:typeId/rename', ( { typeId } ) => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return ajax.post( '/server/api/types/load.php', { typeId } ).then( ( { name } ) => {
      return {
        form: 'types/EditType',
        size: 'small',
        mode: 'rename',
        typeId,
        initialName: name
      };
    } );
  } );

  route( 'DeleteType', '/admin/types/:typeId/delete', ( { typeId } ) => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return ajax.post( '/server/api/types/load.php', { typeId, used: true } ).then( ( { name, used } ) => {
      return {
        form: 'types/DeleteType',
        size: 'small',
        typeId,
        name,
        used
      };
    } );
  } );

  route( 'AddAttribute', '/admin/types/:typeId/attributes/add', ( { typeId } ) => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return ajax.post( '/server/api/types/load.php', { typeId } ).then( ( { name } ) => {
      return {
        form: 'types/EditAttribute',
        mode: 'add',
        typeId,
        typeName: name
      };
    } );
  } );

  route( 'EditAttribute', '/admin/types/:typeId/attributes/:attributeId/edit', ( { typeId, attributeId } ) => {
    return ajax.post( '/server/api/types/attributes/load.php', { typeId, attributeId, details: true } ).then( ( { name, type, details } ) => {
      return {
        form: 'types/EditAttribute',
        mode: 'edit',
        typeId,
        attributeId,
        initialName: name,
        initialType: type,
        initialDetails: details
      };
    } );
  } );

  route( 'DeleteAttribute', '/admin/types/:typeId/attributes/:attributeId/delete', ( { typeId, attributeId } ) => {
    return ajax.post( '/server/api/types/attributes/load.php', { typeId, attributeId, used: true } ).then( ( { name, used } ) => {
      return {
        form: 'types/DeleteAttribute',
        size: 'small',
        typeId,
        attributeId,
        name,
        used
      };
    } );
  } );

  route( 'ReorderAttributes', '/admin/types/:typeId/attributes/reorder', ( { typeId } ) => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return ajax.post( '/server/api/types/load.php', { typeId, attributes: true } ).then( ( { name, attributes } ) => {
      return {
        form: 'types/ReorderAttributes',
        typeId,
        name,
        initialAttributes: attributes
      };
    } );
  } );

  route( 'ViewSettings', '/admin/types/:typeId/views', ( { typeId } ) => {
    const isAdministrator = store.state.global.userAccess == Access.AdministratorAccess;
    const data = { typeId, defaultView: isAdministrator, publicViews: isAdministrator, personalViews: true };
    return ajax.post( '/server/api/types/load.php', data ).then( ( { name, defaultView, publicViews, personalViews } ) => {
      return {
        form: 'types/ViewSettings',
        size: 'large',
        typeId,
        name,
        defaultView,
        publicViews,
        personalViews
      };
    } );
  } );

  route( 'EditDefaultView', '/admin/types/:typeId/views/default/edit', ( { typeId } ) => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return ajax.post( '/server/api/types/load.php', { typeId, defaultView: true } ).then( ( { name, defaultView } ) => {
      return {
        form: 'types/EditView',
        mode: 'default',
        typeId,
        typeName: name,
        initialView: defaultView
      };
    } );
  } );

  route( 'AddPublicView', '/admin/types/:typeId/views/add/public', ( { typeId } ) => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return ajax.post( '/server/api/types/load.php', { typeId, defaultView: true } ).then( ( { name, defaultView } ) => {
      return {
        form: 'types/EditView',
        mode: 'add',
        typeId,
        typeName: name,
        isPublic: true,
        initialView: defaultView
      };
    } );
  } );

  route( 'AddPersonalView', '/admin/types/:typeId/views/add/personal', ( { typeId } ) => {
    return ajax.post( '/server/api/types/load.php', { typeId, defaultView: true } ).then( ( { name, defaultView } ) => {
      return {
        form: 'types/EditView',
        mode: 'add',
        typeId,
        typeName: name,
        isPublic: false,
        initialView: defaultView
      };
    } );
  } );

  route( 'EditView', '/admin/types/:typeId/views/:viewId/edit', ( { typeId, viewId } ) => {
    return ajax.post( '/server/api/types/views/load.php', { typeId, viewId, details: true } ).then( ( { name, isPublic, details } ) => {
      return {
        form: 'types/EditView',
        mode: 'edit',
        typeId,
        viewId,
        isPublic,
        initialName: name,
        initialView: details
      };
    } );
  } );

  route( 'CloneView', '/admin/types/:typeId/views/:viewId/clone', ( { typeId, viewId } ) => {
    return ajax.post( '/server/api/types/views/load.php', { typeId, viewId, details: true } ).then( ( { name, isPublic, details } ) => {
      return {
        form: 'types/EditView',
        mode: 'clone',
        typeId,
        viewId,
        isPublic,
        initialName: name,
        initialView: details
      };
    } );
  } );

  route( 'UnpublishView', '/admin/types/:typeId/views/:viewId/unpublish', ( { typeId, viewId } ) => {
    return ajax.post( '/server/api/types/views/load.php', { typeId, viewId } ).then( ( { name } ) => {
      return {
        form: 'types/UnpublishView',
        size: 'small',
        typeId,
        viewId,
        name
      };
    } );
  } );

  route( 'DeleteView', '/admin/types/:typeId/views/:viewId/delete', ( { typeId, viewId } ) => {
    return ajax.post( '/server/api/types/views/load.php', { typeId, viewId } ).then( ( { name, isPublic } ) => {
      return {
        form: 'types/DeleteView',
        size: 'small',
        typeId,
        viewId,
        isPublic,
        name
      };
    } );
  } );
}
