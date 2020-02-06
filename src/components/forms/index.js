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

import Vue from 'vue'

import ErrorMessage from '@/components/forms/ErrorMessage'

const loadedForms = {
  ErrorMessage
};

if ( process.env.TARGET == 'electron' ) {
  const context = require.context( './client', false, /\.vue$/ );

  context.keys().forEach( key => {
    const name = key.replace( /^\.\//, '' ).replace( /\.vue$/, '' );
    loadedForms[ `client/${name}` ] = context( key ).default;
  } );

  loadedForms[ 'about/AboutForm' ] = require( '@/components/forms/about/AboutForm' ).default;
}

const loadedComponents = {};

const componentModules = {
  Draggable: () => import( /* webpackChunkName: "vendor-draggable" */ 'vuedraggable' )
};

const formModules = {
  issues: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-issues" */ `@/components/forms/issues/${name}` ),
  projects: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-projects" */ `@/components/forms/projects/${name}` ),
  types: name => withComponents( import( /* webpackMode: "lazy-once", webpackChunkName: "forms-types" */ `@/components/forms/types/${name}` ), [ 'Draggable' ] ),
  users: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-users" */ `@/components/forms/users/${name}` ),
  alerts: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-alerts" */ `@/components/forms/alerts/${name}` ),
  settings: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-settings" */ `@/components/forms/settings/${name}` ),
  events: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-events" */ `@/components/forms/events/${name}` ),
  export: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-export" */ `@/components/forms/export/${name}` ),
  about: name => import( /* webpackMode: "lazy-once", webpackChunkName: "forms-about" */ `@/components/forms/about/${name}` )
};

export function loadForm( name ) {
  if ( loadedForms[ name ] != null )
    return Promise.resolve();

  const [ moduleName, formName ] = name.split( '/' );

  return formModules[ moduleName ]( formName ).then( form => {
    loadedForms[ name ] = form.default;
  } );
}

export function getForm( name ) {
  return loadedForms[ name ];
}

function loadComponent( name ) {
  if ( loadedComponents[ name ] != null )
    return Promise.resolve();

  return componentModules[ name ]().then( component => {
    Vue.component( name, component.default );
    loadedComponents[ name ] = component.default;
  } );
}

function withComponents( form, components ) {
  return Promise.all( [ form, ...components.map( name => loadComponent( name ) ) ] ).then( modules => modules[ 0 ] );
}
