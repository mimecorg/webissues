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

import { ErrorCode } from '@/constants'
import { makeError } from '@/utils/errors'
import { invariantSettings, parseDecimalNumber, formatDecimalNumber, parseDate, formatDate } from '@/utils/locale'

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.formatter )
      this.$formatter = options.formatter;
    else if ( options.parent && options.parent.$formatter )
      this.$formatter = options.parent.$formatter;
  }
} );

export default function makeFormatter( store, i18n ) {
  return {
    escape,
    convertLinks,
    convertAttributeValue( value, attribute, flags = {} ) {
      return convertAttributeValue( value, attribute, flags, store.state.global.settings );
    },
    convertInitialValue( value, attribute ) {
      return convertInitialValue( value, attribute, store.state.global.userName );
    },
    formatDate( date, flags = {} ) {
      return formatDate( date, flags, store.state.global.settings );
    },
    formatStamp( stamp ) {
      return formatDate( new Date( stamp * 1000 ), { withTime: true }, store.state.global.settings );
    },
    formatFileSize( size ) {
      return formatFileSize( size, i18n, store.state.global.settings );
    }
  }
}

function convertLinks( text ) {
  const mail = '\\b(?:mailto:)?[\\w.%+-]+@[\\w.-]+\\.[a-z]{2,}\\b';
  const url = '(?:\\b(?:(?:https?|ftp|file):\\/\\/|www\\.|ftp\\.)|\\\\\\\\)(?:\\([\\w+&@#\\/\\\\%=~|$?!:,.-]*\\)|[\\w+&@#\\/\\\\%=~|$?!:,.-])*(?:\\([\w+&@#\\/\\\\%=~|$?!:,.-]*\\)|[\\w+&@#\\/\\\\%=~|$])';
  const id = '#\\d+\\b';
  const pattern = new RegExp( mail + '|' + url + '|' + id, 'uig' );

  let result = '';
  let i = 0;
  let match;

  while ( ( match = pattern.exec( text ) ) != null ) {
    if ( match.index > i )
      result += escape( text.substr( i, match.index - i ) );
    const url = convertUrl( match[ 0 ] );
    const extraAttrs = url[ 0 ] != '#' ? ' target="_blank" rel="noopener noreferrer"' : '';
    result += '<a href="' + escape( url ) + '"' + extraAttrs + '>' + escape( match[ 0 ] ) + '</a>';
    i = match.index + match[ 0 ].length;
  }

  if ( i < text.length )
    result += escape( text.substr( i ) );

  return result;
}

function convertAttributeValue( value, attribute, { multiLine = false }, settings ) {
  if ( value == null || value == '' )
    return '';

  switch ( attribute.type ) {
    case 'TEXT':
      if ( attribute[ 'multi-line' ] != 1 || !multiLine )
        value = toSingleLine( value );
      break;

    case 'ENUM':
    case 'USER':
      value = toSingleLine( value );
      break;

    case 'NUMERIC':
      const number = parseDecimalNumber( value, null, null, null, invariantSettings );
      value = formatDecimalNumber( number, attribute.decimal, { stripZeros: attribute.strip == 1 }, settings );
      break;

    case 'DATETIME':
      const date = parseDate( value, { withTime: true, fromUTC: attribute.local == 1 }, invariantSettings );
      value = formatDate( date, { withTime: attribute.time == 1 }, settings );
      break;

    default:
      throw makeError( ErrorCode.InvalidDefinition );
  }

  return value;
}

function convertInitialValue( value, attribute, userName ) {
  if ( value == null || value == '' )
    return '';

  if ( ( attribute.type == 'TEXT' || attribute.type == 'ENUM' || attribute.type == 'USER' ) && value.substr( 0, 4 ) == '[Me]' )
    return userName;

  if ( attribute.type == 'DATETIME' && value.substr( 0, 7 ) == '[Today]' ) {
    const date = new Date();
    const offset = value.substr( 7 );
    if ( offset != '' )
      date.setDate( date.getDate() + Number( offset ) );
    return formatDate( date, { withTime: attribute.time == 1 }, invariantSettings );
  }

  return value;
}

function formatFileSize( size, i18n, settings ) {
  if ( size < 1024 )
    return i18n.t( 'text.bytes', [ formatDecimalNumber( size, 0, {}, settings ) ] );

  size /= 1024;
  if ( size < 1024 )
    return i18n.t( 'text.kB', [ formatDecimalNumber( size, 1, { stripZeros: true }, settings ) ] );

  size /= 1024;
  return i18n.t( 'text.MB', [ formatDecimalNumber( size, 1, { stripZeros: true }, settings ) ] );
}

function toSingleLine( string ) {
  string = string.replace( /^[ \t\n]+/, '' );
  string = string.replace( /[ \t\n]+$/, '' );
  string = string.replace( /[ \t\n]+/g, ' ' );
  return string;
}

const Entities = {
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  '&': '&amp;'
};

function escape( text ) {
  return text.replace( /[<>"&]/g, ch => Entities[ ch ] || ch )
}

function convertUrl( url ) {
  if ( url[ 0 ] == '#' )
    return '#/items/' + url.substr( 1 );
  else if ( url.substr( 0, 4 ).toLowerCase() == 'www.' )
    return 'http://' + url;
  else if ( url.substr( 0, 4 ).toLowerCase() == 'ftp.' )
    return 'ftp://' + url;
  else if ( url.substr( 0, 2 ) == '\\\\' )
    return 'file:///' + url;
  else if ( url.indexOf( ':' ) < 0 )
    return 'mailto:' + url;
  else
    return url;
}
