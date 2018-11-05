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

export default function routeSettings( route, ajax, store ) {
  route( 'ServerSettings', '/settings', () => {
    return ajax.post( '/settings/load.php', { inboxes: true } ).then( ( { serverName, settings, inboxes } ) => {
      return {
        form: 'settings/ServerSettings',
        serverName,
        settings,
        inboxes
      };
    } );
  } );

  route( 'RenameServer', '/settings/server/rename', () => {
    return ajax.post( '/settings/load.php' ).then( ( { serverName } ) => {
      return {
        form: 'settings/RenameServer',
        size: 'small',
        initialName: serverName
      };
    } );
  } );

  route( 'ResetUuid', '/settings/server/uuid', () => {
    if ( store.state.global.userAccess != Access.AdministratorAccess )
      return Promise.reject( makeError( ErrorCode.AccessDenied ) );
    return Promise.resolve( {
      form: 'settings/ResetUuid',
      size: 'small'
    } );
  } );

  route( 'EmailSettings', '/settings/email', () => {
    return ajax.post( '/settings/email/load.php' ).then( ( { settings } ) => {
      return {
        form: 'settings/EmailSettings',
        settings
      };
    } );
  } );

  route( 'AccessSettings', '/settings/access', () => {
    return ajax.post( '/settings/access/load.php' ).then( ( { settings } ) => {
      return {
        form: 'settings/AccessSettings',
        settings
      };
    } );
  } );

  route( 'RegionalSettings', '/settings/regional', () => {
    return ajax.post( '/settings/regional/load.php' ).then( ( { settings, defaultTimeZone, timeZones, formats } ) => {
      return {
        form: 'settings/RegionalSettings',
        settings,
        defaultTimeZone,
        timeZones,
        formats
      };
    } );
  } );

  route( 'AdvancedSettings', '/settings/advanced', () => {
    return ajax.post( '/settings/advanced/load.php' ).then( ( { settings } ) => {
      return {
        form: 'settings/AdvancedSettings',
        settings
      };
    } );
  } );

  route( 'AddInbox', '/settings/inboxes/add', () => {
    return ajax.post( '/settings/load.php' ).then( ( { settings } ) => {
      return {
        form: 'settings/EditInbox',
        mode: 'add',
        initialEngine: 'imap',
        emailEngine: settings.emailEngine
      };
    } );
  } );

  route( 'EditInbox', '/settings/inboxes/:inboxId/edit', ( { inboxId } ) => {
    return ajax.post( '/settings/inboxes/load.php', { inboxId, details: true } ).then( ( { engine, email, details, emailEngine } ) => {
      return {
        form: 'settings/EditInbox',
        mode: 'edit',
        inboxId,
        initialEngine: engine,
        initialEmail: email,
        initialDetails: details,
        emailEngine
      };
    } );
  } );

  route( 'DeleteInbox', '/settings/inboxes/:inboxId/delete', ( { inboxId } ) => {
    return ajax.post( '/settings/inboxes/load.php', { inboxId } ).then( ( { email } ) => {
      return {
        form: 'settings/DeleteInbox',
        size: 'small',
        inboxId,
        email
      };
    } );
  } );

  if ( process.env.TARGET == 'electron' ) {
    route( 'ClientSettings', '/settings/client', () => {
      return Promise.resolve( { form: 'client/ClientSettings', size: 'small' } );
    } );
  }
}
