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

import hljs from 'highlight.js/lib/highlight'

const context = require.context( 'highlight.js/lib/languages', false, /\/(bash|cpp|cs|css|java|javascript|perl|php|python|ruby|sql|vbnet|xml)\.js$/ );

context.keys().forEach( key => {
  const name = key.replace( /^\.\//, '' ).replace( /\.js$/, '' );
  hljs.registerLanguage( name, context( key ) );
} );

export const highlightBlock = hljs.highlightBlock;
