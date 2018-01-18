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

import 'babel-polyfill'
import 'whatwg-fetch'

import Vue from 'vue'

import '@/styles/global.less'

import Application from '@/components/Application'

import commonComponents from '@/components/common'

import makeAjax from '@/services/ajax'
import makeParser from '@/services/parser'
import makeRouter from '@/services/router'

import makeI18n from '@/i18n';
import makeStore from '@/store'

import registerRoutes from '@/routes'

let app = null;

export function main( { baseURL, csrfToken, locale, ...initialState } ) {
  if ( app )
    throw new Error( 'Application already initialized' );

  if ( process.env.NODE_ENV == 'production' )
    __webpack_public_path__ = baseURL + '/assets/';

  const i18n = makeI18n( locale );
  const ajax = makeAjax( baseURL, csrfToken );
  const router = makeRouter();
  const store = makeStore( baseURL, initialState, ajax, router );
  const parser = makeParser( store );

  registerRoutes( router, ajax, parser, store );

  registerComponents( commonComponents );

  app = new Vue( {
    i18n,
    ajax,
    router,
    parser,
    store,
    el: '#application',
    render( createElement ) {
      return createElement( Application );
    }
  } );
}

function registerComponents( components ) {
  for ( const name in components ) {
    if ( components.hasOwnProperty( name ) )
      Vue.component( name, components[ name ] );
  }
}
