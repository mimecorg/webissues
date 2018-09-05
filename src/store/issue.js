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
    lastPromise: null
  };
}

function makeGetters() {
  return {
    filteredAttributes( state, getters, rootState ) {
      if ( rootState.global.settings.hideEmptyValues )
        return state.attributes.filter( a => a.value != '' );
      else
        return state.attributes;
    },
    isItemInHistory( state ) {
      return id => state.history.some( item => item.id == id && ( item.type == Change.CommentAdded || item.type == Change.FileAdded ) );
    },
    processedHistory( state, getters, rootState ) {
      const items = [];
      let change = null;
      for ( let i = 0; i < state.history.length; i++ ) {
        const row = state.history[ i ];
        if ( row.type <= Change.ValueChanged && change != null ) {
          if ( row.createdBy == change.changes[ 0 ].createdBy && ( row.createdDate - change.changes[ 0 ].createdDate ) < 180 ) {
            change.changes.push( row );
            continue;
          }
        }
        if ( change != null ) {
          items.push( change );
          change = null;
        }
        if ( row.type <= Change.ValueChanged )
          change = { ...row, changes: [ row ] };
        else
          items.push( row );
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
      state.lastPromise = null;
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
    setData( state, { details, description, attributes, history, stubs } ) {
      state.details = details;
      if ( description !== true )
        state.description = description;
      state.attributes = attributes;
      if ( process.env.TARGET == 'web' && state.modifiedSince > 0 )
        mergeHistory( state.history, history, stubs, state.modifiedSince );
      else
        state.history = history;
      state.modifiedSince = details.stamp;
    },
    setLastPromise( state, value ) {
      state.lastPromise = value;
    }
  };
}

function makeActions( ajax ) {
  return {
    load( { state, rootState, commit } ) {
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

      let promise;
      if ( process.env.TARGET == 'web' ) {
        promise = ajax.post( '/server/api/issues/load.php', query );
      } else {
        promise = Vue.prototype.$client.loadIssue( rootState.global.serverUUID, state.issueId ).then( cachedData => {
          const filter = query.filter;

          query.filter = History.AllHistory;
          query.modifiedSince = cachedData != null ? cachedData.stamp : 0;

          return ajax.post( '/server/api/issues/load.php', query ).then( loadedData => {
            if ( cachedData == null || cachedData.stamp != loadedData.details.stamp ) {
              if ( cachedData == null ) {
                cachedData = { description: loadedData.description, history: loadedData.history, stamp: loadedData.details.stamp };
              } else {
                if ( loadedData.description !== true )
                  cachedData.description = loadedData.description;
                mergeHistory( cachedData.history, loadedData.history, loadedData.stubs, cachedData.stamp );
                cachedData.stamp = loadedData.details.stamp;
              }
              return Vue.prototype.$client.saveIssue( rootState.global.serverUUID, state.issueId, cachedData ).then( prepareData );
            } else {
              return prepareData();
            }

            function prepareData() {
              const data = { ...loadedData, description: cachedData.description, history: cachedData.history };
              if ( filter != History.AllHistory ) {
                const includeComments = ( filter == History.Comments || filter == History.CommentsAndFiles );
                const includeFiles = ( filter == History.Files || filter == History.CommentsAndFiles );
                data.history = data.history.filter( item => item.type == Change.CommentAdded && includeComments || item.type == Change.FileAdded && includeFiles );
              }
              return data;
            }
          } );
        } );
      }

      commit( 'setLastPromise', promise );

      return new Promise( ( resolve, reject ) => {
        promise.then( data => {
          if ( promise == state.lastPromise ) {
            commit( 'setData', data );
            commit( 'list/setIssueRead', { issueId: state.issueId, stamp: state.unread ? 0 : data.details.stamp }, { root: true } );
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

function mergeHistory( target, history, stubs, modifiedSince ) {
  history.forEach( item => {
    if ( item.id <= modifiedSince ) {
      const index = target.findIndex( i => i.id == item.id );
      if ( index >= 0 )
        target.splice( index, 1, item );
    } else {
      target.push( item );
    }
  } );
  if ( stubs != null ) {
    stubs.forEach( id => {
      const index = target.findIndex( i => i.id == id );
      if ( index >= 0 )
        target.splice( index, 1 );
    } );
  }
}
