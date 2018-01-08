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

import { ErrorCode } from '@/constants'

import ErrorMessage from '@/components/forms/ErrorMessage'

export default function makeWindowModule( router ) {
  return {
    namespaced: true,
    state: makeState(),
    getters: makeGetters(),
    mutations: makeMutations(),
    actions: makeActions( router )
  };
}

function makeState() {
  return {
    route: null,
    childComponent: null,
    childProps: null,
    size: 'small',
    busy: true,
    cancellation: null
  };
}

function makeGetters() {
  return {
    error( state ) {
      if ( state.route != null && state.route.name == 'error' )
        return state.route.error;
      else
        return null;
    }
  };
}

function makeMutations() {
  return {
    clear( state ) {
      state.route = null;
      state.childComponent = null;
      state.childProps = null;
      state.size = 'small';
      state.busy = true;
      if ( state.cancellation != null ) {
        state.cancellation();
        state.cancellation = null;
      }
    },
    setBusy( state, value ) {
      state.busy = value;
    },
    setRoute( state, value ) {
      state.route = value;
    },
    setComponent( state, { component, props, size } ) {
      state.childComponent = component;
      state.childProps = props;
      state.size = size;
    },
    setCancellation( state, cancellation ) {
      state.cancellation = cancellation;
    },
  };
}

function makeActions( router ) {
  return {
    handleRoute( { commit, dispatch }, route ) {
      commit( 'setRoute', route );
      if ( route.name != 'error' ) {
        let cancelled = false;
        commit( 'setCancellation', () => {
          cancelled = true;
        } );
        route.handler( route.params ).then( ( { component, size = 'normal', replace, ...props } ) => {
          if ( !cancelled ) {
            if ( replace != null ) {
              router.replace( replace, props );
            } else {
              commit( 'setComponent', { component: () => component, props, size } );
              commit( 'setBusy', false );
            }
            commit( 'setCancellation', null );
          }
        } ).catch( error => {
          if ( !cancelled ) {
            dispatch( 'showError', error, { root: true } );
            commit( 'setCancellation', null );
          }
        } );
      } else {
        commit( 'setComponent', { component: ErrorMessage, props: { error: route.error }, size: 'small' } );
        commit( 'setBusy', false );
      }
    },

    close( { getters, rootGetters, dispatch } ) {
      if ( getters.error != null ) {
        if ( rootGetters[ 'global/isAuthenticated' ] && getters.error.errorCode == ErrorCode.LoginRequired )
          dispatch( 'redirect', '/index.php', { root: true } );
        else
          dispatch( 'redirect', '/client/index.php', { root: true } );
      } else {
        dispatch( 'pushMainRoute', null, { root: true } );
      }
    }
  };
}
