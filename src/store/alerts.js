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

const UpdateInterval = 1 * 60 * 1000; // 1 minute

export default function makeAlertsModule( ajax ) {
  return {
    namespaced: true,
    state: makeState(),
    getters: makeGetters(),
    mutations: makeMutations(),
    actions: makeActions( ajax )
  };
}

function makeState() {
  return {
    publicAlerts: [],
    personalAlerts: [],
    loading: false,
    timer: null,
    dirty: false
  };
}

function makeGetters() {
  return {
  };
}

function makeMutations() {
  return {
    setDirty( state ) {
      state.dirty = true;
    },
    setData( state, { publicAlerts, personalAlerts } ) {
      state.loading = false;
      state.publicAlerts = publicAlerts;
      state.personalAlerts = personalAlerts;
    },
    beginUpdate( state ) {
      state.loading = true;
      state.dirty = false;
    },
    setTimer( state, value ) {
      state.timer = value
    },
    reset( state ) {
      state.dirty = false;
      state.loading = false;
      state.timer = null;
    }
  };
}

function makeActions( ajax ) {
  return {
    update( { state, rootGetters, dispatch, commit } ) {
      if ( state.loading || !rootGetters[ 'global/isAuthenticated' ] )
        return;
      if ( state.timer != null ) {
        clearTimeout( state.timer );
        commit( 'setTimer', null );
      }
      dispatch( 'load' );
    },

    load( { rootGetters, commit, dispatch } ) {
      if ( rootGetters[ 'window/error' ] ) {
        commit( 'setTimer', null );
        return;
      }
      commit( 'beginUpdate' );
      return ajax.post( '/alerts/status.php' ).then( data => {
        commit( 'setData', data );
        commit( 'setTimer', setTimeout( () => dispatch( 'load' ), UpdateInterval ) );
      } ).catch( err => {
        console.error( err );
        commit( 'reset' );
      } );
    }
  };
}
