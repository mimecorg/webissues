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

import Vue from 'vue'

import { TextFormat, ErrorCode } from '@/constants'
import { makeError } from '@/utils/errors'

export default function routeIssues( route, i18n, ajax, store, formatter ) {
  function loadIssueDetails( issueId ) {
    if ( store.state.issue.issueId != issueId ) {
      store.commit( 'issue/clear' );
      store.commit( 'issue/setIssueId', issueId );
    }
    return store.dispatch( 'issue/load' ).then( () => {
      return { form: 'issues/IssueDetails' };
    } );
  }

  route( 'IssueDetails', '/issues/:issueId', ( { issueId } ) => {
    return loadIssueDetails( issueId );
  } );

  route( 'IssueItem', '/issues/:issueId/items/:itemId', ( { issueId, itemId } ) => {
    return loadIssueDetails( issueId );
  } );

  route( 'GoToItem', '/items/goto', () => {
    return Promise.resolve( { form: 'issues/GoToItem' } );
  } );

  route( 'Item', '/items/:itemId', ( { itemId } ) => {
    return ajax.post( '/issues/find.php', { itemId } ).then( issueId => {
      if ( itemId == issueId )
        return { replace: 'IssueDetails', issueId };
      else
        return { replace: 'IssueItem', issueId, itemId };
    } );
  } );

  route( 'EditIssue', '/issues/:issueId/edit', ( { issueId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return ajax.post( '/issues/load.php', { issueId, attributes: true } ).then( ( { details, attributes } ) => {
      return {
        form: 'issues/EditIssue',
        mode: 'edit',
        issueId,
        typeId: details.typeId,
        initialFolderId: details.folderId,
        initialName: details.name,
        attributes
      };
    } );
  } );

  route( 'AddIssue', '/types/:typeId/issues/add', ( { typeId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    const type = store.state.global.types.find( t => t.id == typeId );
    if ( type == null )
      return Promise.reject( makeError( ErrorCode.UnknownType ) );
    const project = store.getters[ 'list/project' ];
    const projectId = project != null ? project.id : null;
    const folder = store.getters[ 'list/folder' ];
    const folderId = folder != null ? folder.id : null;
    const attributes = type.attributes.map( attribute => ( {
      id: attribute.id,
      value: formatter.convertInitialValue( attribute.default, attribute )
    } ) );
    return Promise.resolve( {
      form: 'issues/EditIssue',
      mode: 'add',
      typeId,
      initialProjectId: projectId,
      initialFolderId: folderId,
      attributes,
      initialFormat: store.state.global.settings.defaultFormat
    } );
  } );

  route( 'CloneIssue', '/issues/:issueId/clone', ( { issueId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return ajax.post( '/issues/load.php', { issueId, description: true, attributes: true } ).then( ( { details, description, attributes } ) => {
      return {
        form: 'issues/EditIssue',
        mode: 'clone',
        issueId,
        typeId: details.typeId,
        initialFolderId: details.folderId,
        initialName: details.name,
        attributes,
        initialDescription: description != null ? description.text : null,
        initialFormat: description != null ? description.format : store.state.global.settings.defaultFormat
      };
    } );
  } );

  route( 'MoveIssue', '/issues/:issueId/move', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId, access: 'admin' } ).then( ( { details } ) => {
      return {
        form: 'issues/MoveIssue',
        issueId,
        typeId: details.typeId,
        initialFolderId: details.folderId,
        name: details.name
      };
    } );
  } );

  route( 'DeleteIssue', '/issues/:issueId/delete', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId, access: 'admin' } ).then( ( { details } ) => {
      return {
        form: 'issues/DeleteIssue',
        issueId,
        name: details.name
      };
    } );
  } );

  route( 'SubscribeIssue', '/issues/:issueId/subscribe', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId } ).then( ( { details } ) => {
      return {
        form: 'issues/SubscribeIssue',
        issueId,
        name: details.name
      };
    } );
  } );

  route( 'UnsubscribeIssue', '/issues/:issueId/unsubscribe', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId } ).then( ( { details } ) => {
      return {
        form: 'issues/UnsubscribeIssue',
        issueId,
        name: details.name
      };
    } );
  } );

  route( 'AddDescription', '/issues/:issueId/description/add', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId, description: true, access: 'adminOrOwner' } ).then( ( { details, description } ) => {
      if ( description != null )
        return Promise.reject( makeError( ErrorCode.DescriptionAlreadyExists ) );
      return {
        form: 'issues/EditDescription',
        mode: 'add',
        issueId,
        issueName: details.name,
        initialFormat: store.state.global.settings.defaultFormat
      };
    } );
  } );

  route( 'ReplyDescription', '/issues/:issueId/description/reply', ( { issueId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return ajax.post( '/issues/load.php', { issueId, description: true } ).then( ( { details, description } ) => {
      if ( description == null )
        return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
      return {
        form: 'issues/EditComment',
        mode: 'add',
        issueId,
        issueName: details.name,
        initialComment: '[quote ' + i18n.t( 'text.Description' ) + ']\n' + description.text + '\n[/quote]\n\n',
        initialFormat: TextFormat.TextWithMarkup
      };
    } );
  } );

  route( 'EditDescription', '/issues/:issueId/description/edit', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId, description: true, access: 'adminOrOwner' } ).then( ( { details, description } ) => {
      if ( description == null )
        return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
      return {
        form: 'issues/EditDescription',
        mode: 'edit',
        issueId,
        issueName: details.name,
        initialDescription: description.text,
        initialFormat: description.format
      };
    } );
  } );

  route( 'DeleteDescription', '/issues/:issueId/description/delete', ( { issueId } ) => {
    return ajax.post( '/issues/load.php', { issueId, description: true, access: 'adminOrOwner' } ).then( ( { details, description } ) => {
      if ( description == null )
        return Promise.reject( makeError( ErrorCode.UnknownDescription ) );
      return {
        form: 'issues/DeleteDescription',
        issueId,
        issueName: details.name
      };
    } );
  } );

  route( 'AddComment', '/issues/:issueId/comments/add', ( { issueId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return ajax.post( '/issues/load.php', { issueId } ).then( ( { details } ) => {
      return {
        form: 'issues/EditComment',
        mode: 'add',
        issueId,
        issueName: details.name,
        initialFormat: store.state.global.settings.defaultFormat
      };
    } );
  } );

  route( 'ReplyComment', '/issues/:issueId/comments/:commentId/reply', ( { issueId, commentId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return ajax.post( '/issues/load.php', { issueId } ).then( ( { details } ) => {
      return ajax.post( '/issues/comments/load.php', { issueId, commentId } ).then( ( { text } ) => {
        return {
          form: 'issues/EditComment',
          mode: 'add',
          issueId,
          issueName: details.name,
          initialComment: '[quote ' + i18n.t( 'text.Comment', [ '#' + commentId ] ) + ']\n' + text + '\n[/quote]\n\n',
          initialFormat: TextFormat.TextWithMarkup
        };
      } );
    } );
  } );

  route( 'EditComment', '/issues/:issueId/comments/:commentId/edit', ( { issueId, commentId } ) => {
    return ajax.post( '/issues/comments/load.php', { issueId, commentId, access: 'adminOrOwner' } ).then( ( { text, format } ) => {
      return {
        form: 'issues/EditComment',
        mode: 'edit',
        issueId,
        commentId,
        initialComment: text,
        initialFormat: format
      };
    } );
  } );

  route( 'DeleteComment', '/issues/:issueId/comments/:commentId/delete', ( { issueId, commentId } ) => {
    return ajax.post( '/issues/comments/load.php', { issueId, commentId, access: 'adminOrOwner' } ).then( () => {
      return {
        form: 'issues/DeleteComment',
        issueId,
        commentId
      };
    } );
  } );

  route( 'AddFile', '/issues/:issueId/files/add', ( { issueId } ) => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return ajax.post( '/issues/load.php', { issueId } ).then( ( { details } ) => {
      return {
        form: 'issues/EditFile',
        mode: 'add',
        issueId,
        issueName: details.name
      };
    } );
  } );

  route( 'EditFile', '/issues/:issueId/files/:fileId/edit', ( { issueId, fileId } ) => {
    return ajax.post( '/issues/files/load.php', { issueId, fileId, access: 'adminOrOwner' } ).then( ( { name, description } ) => {
      return {
        form: 'issues/EditFile',
        mode: 'edit',
        issueId,
        fileId,
        initialName: name,
        initialDescription: description
      };
    } );
  } );

  route( 'DeleteFile', '/issues/:issueId/files/:fileId/delete', ( { issueId, fileId } ) => {
    return ajax.post( '/issues/files/load.php', { issueId, fileId, access: 'adminOrOwner' } ).then( ( { name } ) => {
      return {
        form: 'issues/DeleteFile',
        issueId,
        fileId,
        name
      };
    } );
  } );

  route( 'ImagePreview', '/issues/:issueId/files/:fileId/preview', ( { issueId, fileId } ) => {
      return ajax.post( '/issues/files/load.php', { issueId, fileId } ).then( ( { name } ) => {
        return {
          form: 'issues/ImagePreview',
          issueId,
          fileId,
          name
        };
      } );
  } );

  if ( process.env.TARGET == 'electron' ) {
    route( 'ClientDownload', '/issues/:issueId/files/:fileId/download', ( { issueId, fileId } ) => {
      return ajax.post( '/issues/files/load.php', { issueId, fileId } ).then( ( { name, total, size } ) => {
        return Vue.prototype.$client.findAttachment( store.state.global.serverUUID, fileId ).then( filePath => {
          return {
            form: 'client/ClientDownload',
            issueId,
            fileId,
            name,
            total,
            fileSize: size,
            initialPath: filePath
          };
        } );
      } );
    } );
  }

  route( 'ExportToCSV', '/export/csv', () => {
    return Promise.resolve( { form: 'export/ExportIssues' } );
  } );

  route( 'MarkAsRead', '/mark/read', () => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return Promise.resolve( { form: 'issues/MarkAllIssues', read: true } );
  } );

  route( 'MarkAsUnread', '/mark/unread', () => {
    if ( !store.getters[ 'global/isAuthenticated' ] )
      return Promise.reject( makeError( ErrorCode.LoginRequired ) );
    return Promise.resolve( { form: 'issues/MarkAllIssues', read: false } );
  } );
}
