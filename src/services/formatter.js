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

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.formatter )
      this.$formatter = options.formatter;
    else if ( options.parent && options.parent.$formatter )
      this.$formatter = options.parent.$formatter;
  }
} );

export default function makeFormatter( store ) {
  return {
    convertLinks,
    convertAttributeValue( value, attribute, flags = {} ) {
      return convertAttributeValue( value, attribute, flags, store.state.global.settings );
    },
    convertInitialValue( value, attribute ) {
      return convertInitialValue( value, attribute, store.state.global.userName, store.state.global.settings );
    },
    formatDate( date, flags = {} ) {
      return formatDate( date, flags, store.state.global.settings );
    },
    formatStamp( stamp ) {
      return formatDate( new Date( stamp * 1000 ), { withTime: true }, store.state.global.settings );
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

function convertDecimalNumber( value, decimal, { stripZeros = false }, { groupSeparator, decimalSeparator } ) {
  const fixed = Number.parseFloat( value ).toFixed( decimal );

  let [ integerPart, fractionPart = '' ] = fixed.split( '.', 2 );

  // strip trailing zeros
  if ( decimal > 0 && stripZeros )
    fractionPart = fractionPart.replace( /0+$/, '' );

  // change '-0' to '0'
  if ( integerPart == '-0' && fractionPart == '' )
    integerPart = '0';

  // add thousands separators - see https://stackoverflow.com/a/2901298
  if ( groupSeparator != '' )
    integerPart = integerPart.replace( /\B(?=(\d{3})+(?!\d))/g , groupSeparator );

  return fractionPart != '' ? ( integerPart + decimalSeparator + fractionPart ) : integerPart;
}

function convertDateTime( value, { toLocalTimeZone = false }, settings ) {
  const parts = parseDate( value );
  if ( parts == null )
    return '';

  const year = Number( parts[ 1 ] );
  const month = Number( parts[ 2 ] );
  const day = Number( parts[ 3 ] );

  const hours = parts[ 4 ] != null ? Number( parts[ 4 ] ) : 0;
  const minutes = parts[ 5 ] != null ? Number( parts[ 5 ] ) : 0;

  const date = toLocalTimeZone ? new Date( Date.UTC( year, month - 1, day, hours, minutes ) ) : new Date( year, month - 1, day, hours, minutes );

  return formatDate( date, { withTime: true }, settings );
}

function convertDate( value, { dateOrder, dateSeparator, padMonth, padDay } ) {
  const parts = parseDate( value );
  if ( parts == null )
    return '';

  const year = Number( parts[ 1 ] );
  const month = Number( parts[ 2 ] );
  const day = Number( parts[ 3 ] );

  return makeDateString( year, month, day, dateOrder, dateSeparator, padMonth, padDay );
}

function convertAttributeValue( value, attribute, { multiLine = false }, settings ) {
  if ( value == null || value == '' )
    return '';

  switch ( attribute.type ) {
    case 'TEXT':
      if ( attribute[ 'multi-line' ] == 1 && multiLine )
        return value;
      else
        return toSingleLine( value );

    case 'ENUM':
    case 'USER':
        return toSingleLine( value );

    case 'NUMERIC':
      return convertDecimalNumber( value, attribute.decimal, { stripZeros: attribute.strip == 1 }, settings );

    case 'DATETIME':
      if ( attribute.time == 1 )
        return convertDateTime( value, { toLocalTimeZone: attribute.local == 1 }, settings );
      else
        return convertDate( value, settings );
  }
}

function convertInitialValue( value, attribute, userName, settings ) {
  if ( value == null || value == '' )
    return '';

  if ( ( attribute.type == 'TEXT' || attribute.type == 'ENUM' || attribute.type == 'USER' ) && value.substr( 0, 4 ) == '[Me]' )
    return userName;

  if ( attribute.type == 'DATETIME' && value.substr( 0, 7 ) == '[Today]' ) {
    const date = new Date();
    const offset = value.substr( 7 );
    if ( offset != '' )
      date.setDate( date.getDate() + Number( offset ) );
    return formatDate( date, { withTime: attribute.time == 1 }, settings );
  }

  return value;
}

function formatDate( date, { withTime = false }, { dateOrder, dateSeparator, padMonth, padDay, timeMode, timeSeparator, padHours } ) {
  let value = makeDateString( date.getFullYear(), date.getMonth() + 1, date.getDate(), dateOrder, dateSeparator, padMonth, padDay );
  if ( withTime )
    value += ' ' + formatTime( date, timeMode, timeSeparator, padHours );
  return value;
}

function formatTime( date, timeMode, timeSeparator, padHours ) {
  if ( timeMode == 12 )
    return makeTimeString( ( date.getHours() + 11 ) % 12 + 1, date.getMinutes(), date.getHours() >= 12 ? ' pm' : ' am', timeSeparator, padHours );
  else
    return makeTimeString( date.getHours(), date.getMinutes(), '', timeSeparator, padHours );
}

export function makeDateString( year, month, day, dateOrder, dateSeparator, padMonth, padDay ) {
  if ( padMonth )
    month = month.toString().padStart( 2, '0' );
  if ( padDay )
    day = day.toString().padStart( 2, '0' );
  year = year.toString().padStart( 4, '0' );
  const parts = [];
  for ( let i = 0; i < 3; i++ ) {
    const ch = dateOrder.charAt( i );
    if ( ch == 'y' )
      parts.push( year );
    else if ( ch == 'm' )
      parts.push( month );
    else if ( ch == 'd' )
      parts.push( day );
  }
  return parts.join( dateSeparator );
}

export function makeTimeString( hours, minutes, amPm, timeSeparator, padHours ) {
  if ( padHours )
    hours = hours.toString().padStart( 2, '0' );
  minutes = minutes.toString().padStart( 2, '0' );
  return hours + timeSeparator + minutes + amPm;
}

function toSingleLine( string ) {
  string = string.replace( /^[ \t\n]+/, '' );
  string = string.replace( /[ \t\n]+$/, '' );
  string = string.replace( /[ \t\n]+/g, ' ' );
  return string;
}

function parseDate( value ) {
  return /^(\d\d\d\d)-(\d\d)-(\d\d)(?: (\d\d):(\d\d))?$/.exec( value );
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
