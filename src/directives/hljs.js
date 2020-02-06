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

let highlightBlock = null;

Vue.directive( 'hljs', {
  bind( element, binding ) {
    update( element, binding.value );
  },
  componentUpdated( element, binding ) {
    update( element, binding.value );
  }
} );

function update( element, value ) {
  element.innerHTML = value;
  const blocks = element.querySelectorAll( 'pre.hljs' );
  if ( blocks.length > 0 ) {
    if ( highlightBlock == null ) {
      import( /* webpackChunkName: "vendor-highlight" */ '@/utils/highlight' ).then( hljs => {
        highlightBlock = hljs.highlightBlock;
        highlightAllBlocks( blocks );
      } );
    } else {
      highlightAllBlocks( blocks );
    }
  }
}

function highlightAllBlocks( blocks ) {
  for ( let i = 0; i < blocks.length; i++ )
    highlightBlock( blocks[ i ] );
}
