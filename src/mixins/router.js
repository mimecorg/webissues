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
    if ( options.router != null )
      this.$router = options.router;
    else if ( options.parent != null && options.parent.$router != null )
      this.$router = options.parent.$router;
  },
  mounted() {
    const options = this.$options;
    if ( this.$router != null && options.routeChanged != null ) {
      this.$routeChangedHandler = options.routeChanged.bind( this );
      this.$router.addHandler( this.$routeChangedHandler );
    }
  },
  beforeDestroy() {
    if ( this.$router != null && this.$routeChangedHandler != null )
      this.$router.removeHandler( this.$routeChangedHandler );
  }
} );

export default function makeRouter() {
  const routes = [];

  const handlers = [];

  let lastPath = null;
  let lastRoute = null;

  function getCurrentRoute() {
    const path = getCurrentPath();
    if ( path != lastPath ) {
      lastPath = path;
      lastRoute = resolveRoute( routes, path );
    }
    return lastRoute;
  }

  function onHashChange() {
    const fromRoute = lastRoute;
    callHandlers( handlers, getCurrentRoute(), fromRoute );
  }

  window.addEventListener( 'hashchange', onHashChange );

  return {
    get path() {
      return getCurrentPath();
    },
    get route() {
      return getCurrentRoute();
    },
    push( name, params = {} ) {
      pushPath( buildPath( routes, name, params ) );
    },
    replace( name, params = {} ) {
      replacePath( buildPath( routes, name, params ) );
    },
    redirect( url ) {
      window.location = url;
    },
    register( factory ) {
      factory( ( name, path, handler = null ) => {
        addRoute( routes, name, path, handler );
      } );
    },
    hotUpdate( factory ) {
      factory( ( name, path, handler = null ) => {
        removeRoute( routes, name );
        addRoute( routes, name, path, handler );
      } );
      lastPath = null;
      onHashChange();
    },
    addHandler( handler ) {
      handlers.push( handler );
    },
    removeHandler( handler ) {
      const index = handlers.indexOf( handler );
      if ( index >= 0 )
        handlers.splice( index, 1 );
    },
    destroy() {
      window.removeEventListener( 'hashchange', onHashChange );
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
    params,
    path
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

function callHandlers( handlers, route, fromRoute ) {
  for ( let i = handlers.length - 1; i >= 0; i-- ) {
    if ( handlers[ i ]( route, fromRoute ) )
      break;
  }
}
