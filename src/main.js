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
import VueI18n from 'vue-i18n'
import Vuex from 'vuex'

import '@/styles/global.less'

import Application from '@/components/Application'

import ActionLink from '@/components/common/ActionLink.vue'
import BusyOverlay from '@/components/common/BusyOverlay.vue'
import DropdownButton from '@/components/common/DropdownButton.vue'
import FormButtons from '@/components/common/FormButtons.vue'
import FormGroup from '@/components/common/FormGroup.vue'
import GridView from '@/components/common/GridView.vue'
import InfoPrompt from '@/components/common/InfoPrompt.vue'
import TitleBar from '@/components/common/TitleBar.vue'

import makeAjax from '@/services/ajax'
import makeRouter from '@/services/router'

import makeGlobalModule from '@/store/global'
import makeListModule from '@/store/list'

import applicationRoutes from '@/routes/application'
import makeIssueRoutes from '@/routes/issue'

import en_US from '@/translations/en_US'

Vue.use( VueI18n );
Vue.use( Vuex );

let app = null;

export function main( { baseURL, csrfToken, locale, ...initialState } ) {
  if ( app )
    throw new Error( 'Application already initialized' );

  if ( process.env.NODE_ENV == 'production' )
    __webpack_public_path__ = baseURL + '/assets/';

  const i18n = new VueI18n( {
    locale,
    fallbackLocale: 'en_US',
    messages: { en_US }
  } );

  const ajax = makeAjax( baseURL, csrfToken );

  const store = new Vuex.Store( {
    modules: {
      global: makeGlobalModule( baseURL, initialState, ajax ),
      list: makeListModule( i18n, ajax )
    }
  } );

  const router = makeRouter( [
    applicationRoutes,
    makeIssueRoutes( ajax )
  ] );

  registerComponents( {
    ActionLink,
    BusyOverlay,
    DropdownButton,
    FormButtons,
    FormGroup,
    GridView,
    InfoPrompt,
    TitleBar,
  } );

  app = new Vue( {
    i18n,
    ajax,
    store,
    router,
    el: '#application',
    render( createElement ) {
      return createElement( Application );
    }
  } );

  if ( process.env.NODE_ENV != 'production' && module.hot != null ) {
    module.hot.accept( '@/translations/en_US', () => {
      i18n.setLocaleMessage( 'en_US', en_US );
    } );

    module.hot.accept( [ '@/store/global', '@/store/list' ], () => {
      store.hotUpdate( {
        modules: {
          global: makeGlobalModule( baseURL, initialState, ajax ),
          list: makeListModule( i18n, ajax )
        }
      } );
    } );

    module.hot.accept( [ '@/routes/application', '@/routes/issue' ], () => {
      router.hotUpdate( [
        applicationRoutes,
        makeIssueRoutes( ajax )
      ] );
    } );
  }
}

function registerComponents( components ) {
  for ( name in components ) {
    if ( components.hasOwnProperty( name ) )
      Vue.component( name, components[ name ] );
  }
}
