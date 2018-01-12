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

import { ErrorCode } from '@/constants'

import EditIssue from '@/components/forms/EditIssue'
import GoToItem from '@/components/forms/GoToItem'
import IssueDetails from '@/components/forms/IssueDetails'

export default function makeIssueRoutes( ajax, parser, store ) {
  return function issueRoutes( route ) {
    function loadIssueDetails( issueId ) {
      if ( store.state.issue.issueId != issueId ) {
        store.commit( 'issue/clear' );
        store.commit( 'issue/setIssueId', issueId );
      }
      return store.dispatch( 'issue/load' ).then( () => {
        return { component: IssueDetails, size: 'large' };
      } );
    }

    route( 'IssueDetails', '/issue/:issueId', ( { issueId } ) => {
      return loadIssueDetails( issueId );
    } );

    route( 'IssueItem', '/issue/:issueId/item/:itemId', ( { issueId, itemId } ) => {
      return loadIssueDetails( issueId );
    } );

    route( 'GoToItem', '/item/goto', () => {
      return Promise.resolve( { component: GoToItem } );
    } );

    route( 'Item', '/item/:itemId', ( { itemId } ) => {
      return ajax.post( '/server/api/issue/finditem.php', { itemId } ).then( issueId => {
        if ( itemId == issueId )
          return { replace: 'IssueDetails', issueId };
        else
          return { replace: 'IssueItem', issueId, itemId };
      } );
    } );

    route( 'EditIssue', '/issue/:issueId/edit', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, attributes: true } ).then( ( { details, attributes } ) => {
        return {
          component: EditIssue,
          mode: 'edit',
          issueId,
          typeId: details.typeId,
          projectId: details.projectId,
          name: details.name,
          attributes
        };
      } );
    } );

    route( 'AddIssue', '/issue/add/:typeId', ( { typeId } ) => {
      const type = store.state.global.types.find( t => t.id == typeId );
      if ( type != null ) {
        const project = store.getters[ 'list/project' ];
        const projectId = project != null ? project.id : null;
        const folder = store.getters[ 'list/folder' ];
        const folderId = folder != null ? folder.id : null;
        const attributes = type.attributes.map( attribute => ( {
          id: attribute.id,
          name: attribute.name,
          value: parser.convertInitialValue( attribute.default, attribute, store.state.global.userName )
        } ) );
        return Promise.resolve( {
          component: EditIssue,
          mode: 'add',
          typeId,
          projectId,
          folderId,
          attributes
        } );
      } else {
        return Promise.reject( makeError( ErrorCode.UnknownType ) );
      }
    } );

    route( 'CloneIssue', '/issue/:issueId/clone', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, description: true, attributes: true } ).then( ( { details, description, attributes } ) => {
        return {
          component: EditIssue,
          mode: 'clone',
          issueId,
          typeId: details.typeId,
          projectId: details.projectId,
          folderId: details.folderId,
          name: details.name,
          attributes,
          description: description.text
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
