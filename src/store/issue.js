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

import { Change, History } from '@/constants'

export default function makeIssueModule( ajax ) {
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
    issueId: null,
    modifiedSince: 0,
    filter: History.AllHistory,
    unread: false,
    details: null,
    description: null,
    attributes: [],
    history: [],
    cancellation: null
  };
}

function makeGetters() {
  return {
    isItemInHistory( state ) {
      return id => state.history.some( item => item.id == id && ( item.type == Change.CommentAdded || item.type == Change.FileAdded ) );
    },
    processedHistory( state, getters, rootState ) {
      const items = [];
      let change = null;
      for ( let i = 0; i < state.history.length; i++ ) {
        const row = state.history[ i ];
        if ( row.type <= Change.ValueChanged && change != null ) {
          if ( row.uid == change.changes[ 0 ].uid && ( row.ts - change.changes[ 0 ].ts ) < 180 ) {
            change.changes.push( row );
            continue;
          }
        }
        if ( change != null ) {
          items.push( change );
          change = null;
        }
        if ( row.type <= Change.ValueChanged ) {
          change = row;
          change.changes = [ row ];
        } else {
          items.push( row );
        }
      }
      if ( change != null )
        items.push( change );
      if ( rootState.global.settings.historyOrder == 'desc' )
        items.reverse();
      return items;
    }
  };
}

function makeMutations() {
  return {
    clear( state ) {
      state.issueId = null;
      state.modifiedSince = 0;
      state.filter = History.AllHistory;
      state.unread = false;
      state.details = null;
      state.description = null;
      state.attributes = [];
      state.history = [];
      if ( state.cancellation != null ) {
        state.cancellation();
        state.cancellation = null;
      }
    },
    setIssueId( state, value ) {
      state.issueId = value;
    },
    setFilter( state, value ) {
      state.filter = value;
      state.modifiedSince = 0;
    },
    setUnread( state, value ) {
      state.unread = value;
    },
    setData( state, { details, description, attributes, history } ) {
      state.details = details;
      state.description = description;
      state.attributes = attributes;
      if ( state.modifiedSince > 0 ) {
        history.forEach( item => {
          if ( item.id <= state.modifiedSince ) {
            const index = state.history.findIndex( i => i.id == item.id );
            if ( index >= 0 )
              state.history.splice( index, 1, item );
          } else {
            state.history.push( item );
          }
        } );
      } else {
        state.history = history;
      }
      state.modifiedSince = details.stamp;
    },
    setCancellation( state, cancellation ) {
      state.cancellation = cancellation;
    }
  };
}

function makeActions( ajax ) {
  return {
    load( { state, commit } ) {
      let cancelled = false;
      commit( 'setCancellation', () => {
        cancelled = true;
      } );
      const query = {
        issueId: state.issueId,
        description: true,
        attributes: true,
        history: true,
        modifiedSince: state.modifiedSince,
        filter: state.filter,
        html: true,
        unread: state.unread
      };
      return new Promise( ( resolve, reject ) => {
        ajax.post( '/server/api/issue/load.php', query ).then( data => {
          if ( !cancelled ) {
            commit( 'setData', data );
            commit( 'list/setIssueRead', { issueId: state.issueId, stamp: state.unread ? 0 : data.details.stamp }, { root: true } );
            commit( 'setCancellation', null );
            resolve();
          }
        } ).catch( error => {
          if ( !cancelled ) {
            commit( 'setCancellation', null );
            reject( error );
          }
        } );
      } );
    }
  };
}
