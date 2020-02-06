/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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

import '@/styles/global.less'

import { shell, ipcRenderer } from 'electron'

import url from 'url'

import Vue from 'vue'

import '@/components/common'

import Client from '@/components/Client'

import makeAjax from '@/mixins/ajax'
import { makeClientParser } from '@/mixins/parser'
import '@/mixins/fields'
import '@/mixins/form'

import makeI18n, { fromSystemLocale } from '@/i18n'

import { version } from '../package.json'

if ( process.env.NODE_ENV == 'production' )
  __webpack_public_path__ = './assets/';

Vue.prototype.$client = makeClientAPI();

let settings = null;

let client = null;

ipcRenderer.on( 'start-client', ( event, initialSettings, sytemLocale ) => {
  if ( client != null )
    throw new Error( 'Client already started' );

  settings = initialSettings;

  const locale = fromSystemLocale( sytemLocale );

  makeI18n( locale ).then( i18n => {
    const ajax = makeAjax( settings.baseURL, null );
    const parser = makeClientParser();

    client = new Vue( {
      i18n,
      ajax,
      parser,
      el: '#application',
      render( createElement ) {
        return createElement( Client );
      }
    } );
  } );
} );

function makeClientAPI() {
  let startApplication = null;
  let destroyApplication = null;

  let sessionData = null;

  let progressHandler = null;
  let doneHandler = null;

  const clientAPI = {
    get version() {
      return version;
    },

    get settings() {
      return settings;
    },

    saveSettings() {
      ipcRenderer.send( 'save-settings', settings );
    },

    startApplication( { userId, userName, userAccess, csrfToken, locale } ) {
      import( /* webpackChunkName: "application" */ '@/application' ).then( application => {
        startApplication = application.startApplication;
        destroyApplication = application.destroyApplication;

        client.$destroy();
        client = null;

        sessionData = { userId, userName, userAccess, csrfToken, locale };

        startApplication( {
          baseURL: settings.baseURL,
          serverName: settings.serverName,
          serverVersion: settings.serverVersion,
          ...sessionData
        } );
      } );
    },

    restartApplication() {
      destroyApplication();
      window.location.hash = '';

      startApplication( {
        baseURL: settings.baseURL,
        serverName: settings.serverName,
        serverVersion: settings.serverVersion,
        ...sessionData
      } );
    },

    restartClient() {
      if ( client != null ) {
        client.$destroy();
        client = null;
      } else {
        destroyApplication();
        window.location.hash = '';
      }

      ipcRenderer.send( 'restart-client', settings );
    },

    openURL( url ) {
      shell.openExternal( url );
    },

    openFile( path ) {
      shell.openExternal( clientAPI.pathToURL( path ) );
    },

    pathToURL( path ) {
      return url.format( { pathname: path, protocol: 'file:', slashes: true } );
    },

    isSupportedVersion( serverVersion ) {
      return /^2\..+$/.test( serverVersion );
    },

    findAttachment( serverUUID, fileId ) {
      return new Promise( ( resolve, reject ) => {
        ipcRenderer.once( 'find-attachment-result', ( event, errorMessage, filePath ) => {
          if ( errorMessage != null )
            return reject( new Error( errorMessage ) );
          resolve( filePath );
        } );

        ipcRenderer.send( 'find-attachment', serverUUID, fileId );
      } );
    },

    downloadAttachment( serverUUID, fileId, name, size, url, progressCallback, doneCallback ) {
      progressHandler = ( event, received ) => {
        progressCallback( received );
      }

      doneHandler = ( event, errorMessage, filePath ) => {
        if ( errorMessage != null )
          doneCallback( new Error( errorMessage ), null );
        else
          doneCallback( null, filePath );

        ipcRenderer.removeListener( 'download-attachment-progress', progressHandler );

        doneHandler = null;
        progressHandler = null;
      };

      ipcRenderer.on( 'download-attachment-progress', progressHandler );
      ipcRenderer.once( 'download-attachment-result', doneHandler );

      ipcRenderer.send( 'download-attachment', serverUUID, fileId, name, size, url );
    },

    abortAttachment() {
      if ( progressHandler != null )
        ipcRenderer.removeListener( 'download-attachment-progress', progressHandler );
      if ( doneHandler != null )
        ipcRenderer.removeListener( 'download-attachment-result', doneHandler );

      progressHandler = null;
      doneHandler = null;

      ipcRenderer.send( 'abort-attachment' );
    },

    saveAttachment( filePath, name ) {
      return new Promise( ( resolve, reject ) => {
        ipcRenderer.once( 'save-attachment-result', ( event, errorMessage, targetPath ) => {
          if ( errorMessage != null )
            reject( new Error( errorMessage ) );
          else
            resolve( targetPath );
        } );

        ipcRenderer.send( 'save-attachment', filePath, name );
      } );
    },

    loadIssue( serverUUID, issueId ) {
      return new Promise( ( resolve, reject ) => {
        ipcRenderer.once( 'load-issue-result', ( event, errorMessage, data ) => {
          if ( errorMessage != null )
            return reject( new Error( errorMessage ) );
          resolve( data );
        } );

        ipcRenderer.send( 'load-issue', serverUUID, issueId );
      } );
    },

    saveIssue( serverUUID, issueId, data ) {
      return new Promise( ( resolve, reject ) => {
        ipcRenderer.once( 'save-issue-result', ( event, errorMessage ) => {
          if ( errorMessage != null )
            return reject( new Error( errorMessage ) );
          resolve();
        } );

        ipcRenderer.send( 'save-issue', serverUUID, issueId, data );
      } );
    }
  };

  return clientAPI;
}
