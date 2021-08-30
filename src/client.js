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

const ipcAPI = window.__WI_API;

Vue.prototype.$client = makeClientAPI();

let settings = null;

let client = null;

ipcAPI.onStartClient( ( initialSettings, sytemLocale ) => {
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

  const clientAPI = {
    get version() {
      return version;
    },

    get settings() {
      return settings;
    },

    saveSettings() {
      ipcAPI.saveSettings( settings );
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

      ipcAPI.restartClient( settings );
    },

    openURL( url ) {
      ipcAPI.openURL( url );
    },

    openFile( path ) {
      ipcAPI.openURL( ipcAPI.pathToURL( path ) );
    },

    pathToURL( path ) {
      return ipcAPI.pathToURL( path );
    },

    isSupportedVersion( serverVersion ) {
      return /^2\..+$/.test( serverVersion );
    },

    findAttachment( serverUUID, fileId ) {
      return ipcAPI.findAttachment( serverUUID, fileId );
    },

    downloadAttachment( serverUUID, fileId, name, size, url, progressCallback, doneCallback ) {
      ipcAPI.downloadAttachment( serverUUID, fileId, name, size, url, progressCallback, doneCallback );
    },

    abortAttachment() {
      ipcAPI.abortAttachment();
    },

    saveAttachment( filePath, name ) {
      return ipcAPI.saveAttachment( filePath, name );
    },

    loadIssue( serverUUID, issueId ) {
      return ipcAPI.loadIssue( serverUUID, issueId );
    },

    saveIssue( serverUUID, issueId, data ) {
      return ipcAPI.saveIssue( serverUUID, issueId, data );
    }
  };

  return clientAPI;
}
