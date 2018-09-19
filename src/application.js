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

import '@/components/common'

import Application from '@/components/Application'

import makeAjax from '@/mixins/ajax'
import makeParser from '@/mixins/parser'
import makeFormatter from '@/mixins/formatter'
import makeRouter from '@/mixins/router'
import '@/mixins/fields'

import '@/directives/hljs'

import makeI18n from '@/i18n'
import makeStore from '@/store'

import registerRoutes from '@/routes'

let app = null;

export function startApplication( { baseURL, csrfToken, locale, ...initialState } ) {
  if ( app != null )
    throw new Error( 'Application already started' );

  if ( process.env.NODE_ENV == 'production' && process.env.TARGET == 'web' )
    __webpack_public_path__ = baseURL + '/assets/';

  makeI18n( locale ).then( i18n => {
    const ajax = makeAjax( baseURL, csrfToken );
    const router = makeRouter();
    const store = makeStore( baseURL, initialState, ajax, router );
    const parser = makeParser( store );
    const formatter = makeFormatter( store, i18n );

    registerRoutes( router, i18n, ajax, store, formatter );

    app = new Vue( {
      i18n,
      ajax,
      router,
      parser,
      formatter,
      store,
      el: '#application',
      render( createElement ) {
        return createElement( Application );
      }
    } );
  } );
}

export function destroyApplication() {
  app.$destroy();
  app.$options.router.destroy();
  app = null;
}

if ( process.env.TARGET == 'web' )
  window.WebIssues = Object.assign( window.WebIssues || {}, { startApplication } );
