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

const PageSize = 15;

export default function makeEventsModule( ajax ) {
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
    offset: 0,
    filter: null,
    events: [],
    totalCount: 0,
    lastPromise: null
  };
}

function makeGetters() {
  return {
    firstIndex( state ) {
      if ( state.totalCount > 0 )
        return state.offset + 1;
      else
        return null;
    },
    lastIndex( state ) {
      if ( state.totalCount > 0 )
        return Math.min( state.offset + PageSize, state.totalCount );
      else
        return null;
    }
  };
}

function makeMutations() {
  return {
    clear( state ) {
      state.offset = 0;
      state.filter = null;
      state.events = [];
      state.totalCount = 0;
      state.lastPromise = null;
    },
    setFilter( state, { filter } ) {
      state.filter = filter;
      state.offset = 0;
    },
    setPreviousPage( state ) {
      state.offset -= PageSize;
    },
    setNextPage( state ) {
      state.offset += PageSize;
    },
    setData( state, { events, totalCount } ) {
      state.events = events;
      state.totalCount = totalCount;
    },
    setLastPromise( state, value ) {
      state.lastPromise = value;
    }
  };
}

function makeActions( ajax ) {
  return {
    load( { state, commit } ) {
      const promise = ajax.post( '/events/list.php', { type: state.filter, offset: state.offset, limit: PageSize } );
      commit( 'setLastPromise', promise );
      return new Promise( ( resolve, reject ) => {
        promise.then( data => {
          if ( promise == state.lastPromise ) {
            commit( 'setData', data );
            commit( 'setLastPromise', null );
            resolve();
          }
        } ).catch( error => {
          if ( promise == state.lastPromise ) {
            commit( 'setLastPromise', null );
            reject( error );
          }
        } );
      } );
    }
  };
}
