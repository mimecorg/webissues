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

import '@/styles/global.less'

import { shell, ipcRenderer } from 'electron'

import Vue from 'vue'

import Client from '@/components/Client'

import makeAjax from '@/services/ajax'
import { makeClientParser } from '@/services/parser'

import makeI18n from '@/i18n'

import { startApplication, destroyApplication } from '@/application';

if ( process.env.NODE_ENV == 'production' )
  __webpack_public_path__ = './assets/';

Vue.prototype.$client = makeClientAPI();

let settings = null;

let client = null;

ipcRenderer.on( 'start-client', ( event, arg ) => {
  if ( client != null )
    throw new Error( 'Client already started' );

  settings = arg;

  const i18n = makeI18n( 'en_US' );
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

function makeClientAPI() {
  let sessionData = null;

  return {
    get settings() {
      return settings;
    },

    saveSettings() {
      ipcRenderer.send( 'save-settings', settings );
    },

    startApplication( { userId, userName, userAccess, csrfToken } ) {
      client.$destroy();
      client = null;

      sessionData = { userId, userName, userAccess, csrfToken };

      startApplication( {
        baseURL: settings.baseURL,
        csrfToken,
        locale: 'en_US',
        serverName: settings.serverName,
        serverVersion: settings.serverVersion,
        userId,
        userName,
        userAccess
      } );
    },

    restartApplication() {
      destroyApplication();
      window.location.hash = '';

      startApplication( {
        baseURL: settings.baseURL,
        locale: 'en_US',
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

    openExternal: shell.openExternal
  };
}
