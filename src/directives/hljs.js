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

import hljs from 'highlight.js/lib/highlight'

import bash from 'highlight.js/lib/languages/bash'
import cpp from 'highlight.js/lib/languages/cpp'
import cs from 'highlight.js/lib/languages/cs'
import css from 'highlight.js/lib/languages/cs'
import java from 'highlight.js/lib/languages/java'
import javascript from 'highlight.js/lib/languages/javascript'
import perl from 'highlight.js/lib/languages/perl'
import php from 'highlight.js/lib/languages/php'
import python from 'highlight.js/lib/languages/python'
import ruby from 'highlight.js/lib/languages/ruby'
import sql from 'highlight.js/lib/languages/sql'
import vbnet from 'highlight.js/lib/languages/vbnet'
import xml from 'highlight.js/lib/languages/xml'

hljs.registerLanguage( 'bash', bash );
hljs.registerLanguage( 'cpp', cpp );
hljs.registerLanguage( 'cs', cs );
hljs.registerLanguage( 'css', css );
hljs.registerLanguage( 'java', java );
hljs.registerLanguage( 'javascript', javascript );
hljs.registerLanguage( 'perl', perl );
hljs.registerLanguage( 'php', php );
hljs.registerLanguage( 'python', python );
hljs.registerLanguage( 'ruby', ruby );
hljs.registerLanguage( 'sql', sql );
hljs.registerLanguage( 'vbnet', vbnet );
hljs.registerLanguage( 'xml', xml );

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
  for ( let i = 0; i < blocks.length; i++ )
    hljs.highlightBlock( blocks[ i ] );
}
