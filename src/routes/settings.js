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

export default function routeSettings( route, ajax, store ) {
  route( 'ServerSettings', '/settings', () => {
    return ajax.post( '/settings/load.php' ).then( ( { serverName, settings } ) => {
      return {
        form: 'settings/ServerSettings',
        serverName,
        settings
      };
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

  if ( process.env.TARGET == 'electron' ) {
    route( 'ClientSettings', '/settings/client', () => {
      return Promise.resolve( { form: 'client/ClientSettings', size: 'small' } );
    } );
  }
}
