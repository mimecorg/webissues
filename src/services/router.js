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

import pathToRegexp from 'path-to-regexp'
import Vue from 'vue'

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.router )
      this.$router = options.router;
    else if ( options.parent && options.parent.$router )
      this.$router = options.parent.$router;
  }
} );

export default function makeRouter( factories ) {
  const routes = [];

  let lastPath = null;
  let lastRoute = null;

  factories.forEach( factory => {
    factory( ( name, path, handler = null ) => {
      addRoute( routes, name, path, handler );
    } );
  } );

  return {
    get route() {
      const path = getCurrentPath();
      if ( path != lastPath ) {
        lastPath = path;
        lastRoute = resolveRoute( routes, path );
      }
      return lastRoute;
    },
    push( name, params = {} ) {
      pushPath( buildPath( routes, name, params ) );
    },
    replace( name, params = {} ) {
      replacePath( buildPath( routes, name, params ) );
    },
    hotUpdate( factories ) {
      factories.forEach( factory => {
        factory( ( name, path, handler = null ) => {
          removeRoute( routes, name );
          addRoute( routes, name, path, handler );
        } );
      } );
    }
  };
}

function addRoute( routes, name, path, handler ) {
  const keys = [];
  const tokens = pathToRegexp.parse( path );
  for ( let i = 0; i < tokens.length; i++ ) {
    if ( typeof tokens[ i ] == 'object' )
      tokens[ i ].pattern = '[1-9][0-9]*';
  }
  const regexp = pathToRegexp.tokensToRegExp( tokens, keys, { sensitive: true, strict: true } );
  const buildPath = pathToRegexp.tokensToFunction( tokens );
  routes.push( { name, keys, regexp, buildPath, handler } );
}

function removeRoute( routes, name ) {
  const index = routes.findIndex( r => r.name == name );
  if ( index >= 0 )
    routes.splice( index, 1 );
}

function resolveRoute( routes, path ) {
  let matches = null;
  const route = routes.find( r => {
    matches = r.regexp.exec( path );
    return matches != null;
  } );
  if ( route == null )
    return null;
  const params = {};
  for ( let i = 0; i < route.keys.length; i++ ) {
    const value = Number( matches[ i + 1 ] );
    if ( value >= 2**31 )
      return null;
    params[ route.keys[ i ].name ] = value;
  };
  return {
    name: route.name,
    handler: route.handler,
    params
  };
}

function buildPath( routes, name, params ) {
  const route = routes.find( r => r.name == name );
  if ( route == null )
    throw new Error( 'Unknown route: ' + name );
  return route.buildPath( params );
}

function getCurrentPath() {
  const href = window.location.href;
  const index = href.indexOf( '#' );
  if ( index >= 0 )
    return href.slice( index + 1 );
  return '';
}

function pushPath( path ) {
  window.location.hash = path;
}

function replacePath( path ) {
    let href = window.location.href;
    const index = href.indexOf( '#' );
    if ( index >= 0 )
      href = href.slice( 0, index );
    window.location.replace( href + '#' + path );
}
