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
import Vuex from 'vuex'

import makeStoreRoot from '@/store/root'
import makeGlobalModule from '@/store/global'
import makeIssueModule from '@/store/issue'
import makeListModule from '@/store/list'
import makeWindowModule from '@/store/window'

Vue.use( Vuex );

export default function makeStore( baseURL, initialState, ajax, router ) {
  function makeStoreOptions() {
    return {
      ...makeStoreRoot( router ),
      modules: {
        global: makeGlobalModule( baseURL, initialState, ajax ),
        issue: makeIssueModule( ajax ),
        list: makeListModule( ajax ),
        window: makeWindowModule()
      }
    };
  }

  const store = new Vuex.Store( makeStoreOptions() );

  if ( process.env.NODE_ENV != 'production' && module.hot != null ) {
    module.hot.accept( [ '@/store/root', '@/store/global', '@/store/issue', '@/store/list', '@/store/window' ], () => {
      store.hotUpdate( makeStoreOptions() );
    } );
  }

  return store;
}
