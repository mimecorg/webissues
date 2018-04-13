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

import 'babel-polyfill'
import 'whatwg-fetch'

import '@/styles/global.less'

let expanded = false;

export function initialize() {
  const toggleButton = document.getElementById( 'toggle-button' );
  if ( toggleButton != null )
    toggleButton.addEventListener( 'click', toggle );
  window.addEventListener( 'resize', handleWindowResize );
  focusFirstControl();
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
  const errorDivs = document.getElementsByClassName( 'has-error' );
  if ( errorDivs.length > 0 ) {
    const errorControls = errorDivs[ 0 ].getElementsByClassName( 'form-control' );
    if ( errorControls.length > 0 )
      errorControls[ 0 ].focus();
  } else {
    const allControls = document.getElementsByClassName( 'form-control' );
    if ( allControls.length > 0 )
      allControls[ 0 ].focus();
  }
}
