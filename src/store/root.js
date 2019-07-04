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

import { makeRouteError } from '@/utils/errors'

const LoadingState = {
  Idle: 0,
  GlobalUpdate: 1,
  ListUpdate: 2
};

export default function makeStoreRoot( router ) {
  return {
    state: makeState(),
    getters: makeGetters(),
    mutations: makeMutations(),
    actions: makeActions( router )
  };
}

function makeState() {
  return {
    loadingState: LoadingState.Idle,
    mainRoute: null
  };
}

function makeGetters() {
  return {
    busy( state ) {
      return state.loadingState != LoadingState.Idle;
    }
  };
}

function makeMutations() {
  return {
    setLoadingState( state, value ) {
      state.loadingState = value;
    },
    setMainRoute( state, value ) {
      state.mainRoute = value;
    }
  };
}

function makeActions( router ) {
  return {
    initialize( { commit, dispatch } ) {
      const route = router.route;
      if ( route == null ) {
        dispatch( 'showError', makeRouteError( router.path ) );
      } else {
        if ( route.handler == null ) {
          commit( 'setMainRoute', route );
          commit( 'list/setFilters', route.params );
        }
        dispatch( 'updateGlobal' ).then( () => {
          dispatch( 'alerts/update' );
        } );
      }
    },

    destroy( { commit } ) {
      commit( 'list/clear' );
    },

    navigate( { state, getters, commit, dispatch }, route ) {
      if ( route == null ) {
        dispatch( 'showError', makeRouteError( router.path ) );
      } else if ( route.handler == null ) {
        commit( 'setMainRoute', route );
        commit( 'window/clear' );
        if ( getters[ 'list/areFiltersEqual' ]( route.params ) ) {
          if ( state.loadingState != LoadingState.GlobalUpdate ) {
            if ( getters[ 'global/checkUpdate' ]() )
              dispatch( 'updateGlobal' );
            else if ( getters[ 'list/checkUpdate' ]() )
              dispatch( 'updateList' );
            else if ( state.alerts.dirty )
              dispatch( 'alerts/update' );
          }
        } else {
          commit( 'list/clear' );
          commit( 'list/setFilters', route.params );
          if ( state.loadingState != LoadingState.GlobalUpdate ) {
            if ( getters[ 'global/checkUpdate' ]() )
              dispatch( 'updateGlobal' );
            else if ( getters[ 'list/hasFilters' ] )
              dispatch( 'updateList' );
            else
              dispatch( 'finishLoading' );
          }
        }
      } else {
        if ( state.loadingState == LoadingState.Idle ) {
          commit( 'window/clear' );
          dispatch( 'window/handleRoute', route );
        }
      }
    },

    reload( { state, getters, dispatch } ) {
      if ( state.loadingState != LoadingState.GlobalUpdate ) {
        if ( getters[ 'global/checkUpdate' ]() )
          dispatch( 'updateGlobal' );
        else if ( getters[ 'list/hasFilters' ] )
          dispatch( 'updateList' );
      }
    },

    updateGlobal( { getters, commit, dispatch } ) {
      commit( 'setLoadingState', LoadingState.GlobalUpdate );
      commit( 'list/cancel' );
      return dispatch( 'global/load' ).then( () => {
        if ( getters[ 'list/hasFilters' ] )
          dispatch( 'updateList' );
        else
          dispatch( 'finishLoading' );
      } ).catch( error => {
        dispatch( 'showError', error );
      } );
    },

    updateList( { commit, dispatch } ) {
      commit( 'setLoadingState', LoadingState.ListUpdate );
      commit( 'list/cancel' );
      dispatch( 'list/load' ).then( () => {
        dispatch( 'finishLoading' );
      } ).catch( error => {
        dispatch( 'showError', error );
      } );
    },

    finishLoading( { state, commit, dispatch } ) {
      commit( 'setLoadingState', LoadingState.Idle );
      const route = router.route;
      if ( route != null ) {
        if ( route.handler != null ) {
          Vue.nextTick( () => {
            commit( 'window/clear' );
            dispatch( 'window/handleRoute', route );
          } );
        } else if ( state.alerts.dirty ) {
          dispatch( 'alerts/update' );
        }
      }
    },

    showError( { commit, dispatch }, error ) {
      commit( 'setLoadingState', LoadingState.Idle );
      commit( 'list/clear' );
      Vue.nextTick( () => {
        commit( 'window/clear' );
        dispatch( 'window/handleRoute', { name: 'error', error } )
      } );
      console.error( error );
    },

    pushMainRoute( { state } ) {
      if ( state.mainRoute != null )
        router.push( state.mainRoute.name, state.mainRoute.params );
      else
        router.push( 'Home' );
    },

    redirect( { state }, url ) {
      if ( process.env.TARGET == 'web' )
        router.redirect( state.global.baseURL + url );
    }
  };
}
