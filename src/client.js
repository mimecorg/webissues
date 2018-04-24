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

Vue.prototype.$client = {
  switchToApplication,
  switchToClient,
  openExternal: shell.openExternal
};

let client = null;

ipcRenderer.on( 'start-client', ( event, arg ) => {
  if ( client != null )
    throw new Error( 'Client already started' );

  const i18n = makeI18n( 'en_US' );
  const ajax = makeAjax( 'http://localhost/webissues', null );
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

function switchToApplication( { userId, userName, userAccess, csrfToken } ) {
  client.$destroy();
  client = null;

  startApplication( {
    baseURL: 'http://localhost/webissues',
    csrfToken,
    locale: 'en_US',
    serverName: 'My WebIssues Server',
    serverVersion: '2.0',
    userId,
    userName,
    userAccess
  } );
}

function switchToClient() {
  destroyApplication();

  window.location.hash = '';

  ipcRenderer.send( 'switch-to-client' );
}
