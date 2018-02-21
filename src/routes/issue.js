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

import { TextFormat, ErrorCode } from '@/constants'

import DeleteComment from '@/components/forms/DeleteComment'
import DeleteDescription from '@/components/forms/DeleteDescription'
import DeleteFile from '@/components/forms/DeleteFile'
import DeleteIssue from '@/components/forms/DeleteIssue'
import EditComment from '@/components/forms/EditComment'
import EditDescription from '@/components/forms/EditDescription'
import EditFile from '@/components/forms/EditFile'
import EditIssue from '@/components/forms/EditIssue'
import GoToItem from '@/components/forms/GoToItem'
import IssueDetails from '@/components/forms/IssueDetails'
import MoveIssue from '@/components/forms/MoveIssue'

export default function makeIssueRoutes( i18n, ajax, store, parser ) {
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
      return ajax.post( '/server/api/issue/find.php', { itemId } ).then( issueId => {
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
          value: parser.convertInitialValue( attribute.default, attribute )
        } ) );
        return Promise.resolve( {
          component: EditIssue,
          mode: 'add',
          typeId,
          projectId,
          folderId,
          attributes,
          descriptionFormat: store.state.global.settings.defaultFormat
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
          description: description != null ? description.text : null,
          descriptionFormat: description != null ? description.format : store.state.global.settings.defaultFormat
        };
      } );
    } );

    route( 'MoveIssue', '/issue/:issueId/move', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, access: 'admin' } ).then( ( { details } ) => {
        return {
          component: MoveIssue,
          issueId,
          typeId: details.typeId,
          projectId: details.projectId,
          folderId: details.folderId,
          name: details.name
        };
      } );
    } );

    route( 'DeleteIssue', '/issue/:issueId/delete', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, access: 'admin' } ).then( ( { details } ) => {
        return {
          component: DeleteIssue,
          issueId,
          name: details.name
        };
      } );
    } );

    route( 'AddDescription', 'issue/:issueId/description/add', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, description: true, access: 'adminOrOwner' } ).then( ( { details, description } ) => {
        if ( description != null )
          return Promise.reject( makeError( ErrorCode.DescriptionAlreadyExists ) );
        return {
          component: EditDescription,
          mode: 'add',
          issueId,
          issueName: details.name,
          descriptionFormat: store.state.global.settings.defaultFormat
        };
      } );
    } );

    route( 'ReplyDescription', 'issue/:issueId/description/reply', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, description: true } ).then( ( { details, description } ) => {
        if ( description == null )
          return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
        return {
          component: EditComment,
          mode: 'add',
          issueId,
          issueName: details.name,
          comment: '[quote ' + i18n.t( 'EditComment.DescriptionQuote' ) + ']\n' + description.text + '\n[/quote]\n\n',
          commentFormat: TextFormat.TextWithMarkup
        };
      } );
    } );

    route( 'EditDescription', 'issue/:issueId/description/edit', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, description: true, access: 'adminOrOwner' } ).then( ( { details, description } ) => {
        if ( description == null )
          return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
        return {
          component: EditDescription,
          mode: 'edit',
          issueId,
          issueName: details.name,
          description: description.text,
          descriptionFormat: description.format
        };
      } );
    } );

    route( 'DeleteDescription', 'issue/:issueId/description/delete', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId, description: true, access: 'adminOrOwner' } ).then( ( { details, description } ) => {
        if ( description == null )
          return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
        return {
          component: DeleteDescription,
          size: 'small',
          issueId,
          issueName: details.name
        };
      } );
    } );

    route( 'AddComment', 'issue/:issueId/comment/add', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId } ).then( ( { details } ) => {
        return {
          component: EditComment,
          mode: 'add',
          issueId,
          issueName: details.name,
          commentFormat: store.state.global.settings.defaultFormat
        };
      } );
    } );

    route( 'ReplyComment', 'issue/:issueId/comment/:commentId/reply', ( { issueId, commentId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId } ).then( ( { details } ) => {
        return ajax.post( '/server/api/issue/comment/load.php', { issueId, commentId } ).then( ( { text } ) => {
          return {
            component: EditComment,
            mode: 'add',
            issueId,
            issueName: details.name,
            comment: '[quote ' + i18n.t( 'EditComment.CommentQuote', [ '#' + commentId ] ) + ']\n' + text + '\n[/quote]\n\n',
            commentFormat: TextFormat.TextWithMarkup
          };
        } );
      } );
    } );

    route( 'EditComment', 'issue/:issueId/comment/:commentId/edit', ( { issueId, commentId } ) => {
      return ajax.post( '/server/api/issue/comment/load.php', { issueId, commentId, access: 'adminOrOwner' } ).then( ( { text, format } ) => {
        return {
          component: EditComment,
          mode: 'edit',
          issueId,
          commentId,
          comment: text,
          commentFormat: format
        };
      } );
    } );

    route( 'DeleteComment', 'issue/:issueId/comment/:commentId/delete', ( { issueId, commentId } ) => {
      return ajax.post( '/server/api/issue/comment/load.php', { issueId, commentId, access: 'adminOrOwner' } ).then( () => {
        return {
          component: DeleteComment,
          size: 'small',
          issueId,
          commentId
        };
      } );
    } );

    route( 'AddFile', '/issue/:issueId/file/add', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId } ).then( ( { details } ) => {
        return {
          component: EditFile,
          mode: 'add',
          issueId,
          issueName: details.name
        };
      } );
    } );

    route( 'EditFile', '/issue/:issueId/file/:fileId/edit', ( { issueId, fileId } ) => {
      return ajax.post( '/server/api/issue/file/load.php', { issueId, fileId, access: 'adminOrOwner' } ).then( ( { name, description } ) => {
        return {
          component: EditFile,
          mode: 'edit',
          issueId,
          fileId,
          name,
          description
        };
      } );
    } );

    route( 'DeleteFile', 'issue/:issueId/file/:fileId/delete', ( { issueId, fileId } ) => {
      return ajax.post( '/server/api/issue/file/load.php', { issueId, fileId, access: 'adminOrOwner' } ).then( ( { name } ) => {
        return {
          component: DeleteFile,
          size: 'small',
          issueId,
          fileId,
          name
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
