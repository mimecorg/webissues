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
}
