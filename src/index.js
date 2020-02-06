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

import 'core-js/stable'
import 'whatwg-fetch'

import '@/styles/global.less'

let expanded = false;

function loadApplication( { baseURL, csrfToken, locale, ...initialState } ) {
  if ( process.env.NODE_ENV == 'production' )
    __webpack_public_path__ = baseURL + '/assets/';

  import( /* webpackChunkName: "application" */ '@/application' ).then( ( { startApplication } ) => {
    startApplication( { baseURL, csrfToken, locale, ...initialState } );
  } );
}

function initializePage() {
  const toggleButton = document.getElementById( 'toggle-button' );
  if ( toggleButton != null )
    toggleButton.addEventListener( 'click', toggle );

  window.addEventListener( 'resize', handleWindowResize );

  focusFirstControl();

  const installButton = document.getElementById( 'field-install-installSubmit' );
  if ( installButton != null )
    installButton.addEventListener( 'click', displayBusyOverlay );

  const updateButton = document.getElementById( 'field-update-updateSubmit' );
  if ( updateButton != null )
    updateButton.addEventListener( 'click', displayBusyOverlay );
}

function toggle() {
  expanded = !expanded;
  updateNavbar();
}

function handleWindowResize() {
  if ( window.innerWidth >= 768 && expanded ) {
    expanded = false;
    updateNavbar();
  }
}

function updateNavbar() {
  const collapseDiv = document.getElementById( 'navbar-element-collapse' );
  if ( collapseDiv != null ) {
    collapseDiv.setAttribute( 'class', 'navbar-element collapse' + ( expanded ? ' in' : '' ) );
    collapseDiv.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
  }
}

function focusFirstControl() {
  const errorGroups = document.getElementsByClassName( 'has-error' );
  if ( errorGroups.length > 0 ) {
    const controls = errorGroups[ 0 ].getElementsByClassName( 'form-control' );
    if ( controls.length > 0 )
      controls[ 0 ].focus();
  } else {
    const allGroups = document.getElementsByClassName( 'form-group' );
    if ( allGroups.length > 0 ) {
      const controls = allGroups[ 0 ].getElementsByClassName( 'form-control' );
      if ( controls.length > 0 )
        controls[ 0 ].focus();
    }
  }
}

function displayBusyOverlay() {
  const overlayDivs = document.getElementsByClassName( 'busy-overlay' );
  if ( overlayDivs.length > 0 )
    overlayDivs[ 0 ].style.display = 'block';
}

window.addEventListener( 'DOMContentLoaded', () => {
  if ( window.__WI_OPTIONS != null )
    loadApplication( window.__WI_OPTIONS );
  else
    initializePage();
} );
