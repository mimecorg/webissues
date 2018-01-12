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

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.parser )
      this.$parser = options.parser;
    else if ( options.parent && options.parent.$parser )
      this.$parser = options.parent.$parser;
  }
} );

export default function makeParser() {
  return {
    normalizeString,
    parseInteger,
    checkEmailAddress,
    normalizeDecimalNumber,
    normalizeDate,
    normalizeDateTime,
    normalizeAttributeValue,
    convertInitialValue
  };
}

function normalizeString( string, maxLength = null, { allowEmpty = false, multiLine = false } = {} ) {
  if ( string == null )
    string = '';

  if ( multiLine ) {
    string = string.replace( /\r\n/g, '\n' );
    string = string.replace( /[ \n\t]+$/, '' );
  } else {
    string = string.replace( /^ +/, '' );
    string = string.replace( / +$/, '' );
    string = string.replace( /  +/g, ' ' );
  }

  if ( string == '' ) {
    if ( allowEmpty )
      return string;
    else
      throw makeError( ErrorCode.EmptyValue );
  }

  if ( maxLength != null && string.length > maxLength )
    throw makeError( ErrorCode.StringTooLong );

  if ( multiLine ) {
    // no control characters allowed except TAB and LF
    if ( /[\x00-\x08\x0b-\x1f\x7f]/.test( string ) )
      throw makeError( ErrorCode.InvalidString );
  } else {
    if ( /[\x00-\x1f\x7f]/.test( string ) )
      throw makeError( ErrorCode.InvalidString );
  }

  return string;
}

function parseInteger( value, min = null, max = null ) {
  if ( !/^-?\d+$/.test( value ) )
    throw makeError( ErrorCode.InvalidFormat );

  const number = Number( value );

  if ( number < -2147483648 )
    throw makeError( ErrorCode.NumberTooLittle );
  if ( number > 2147483647 )
    throw makeError( ErrorCode.NumberTooGreat );

  if ( min != null && number < min )
    throw makeError( ErrorCode.NumberTooLittle );
  if ( max != null && number > max )
    throw makeError( ErrorCode.NumberTooGreat );

  return number;
}

function checkEmailAddress( value ) {
  if ( !/^[\w.%+-]+@[\w.-]+\.[a-z]{2,}$/ui.test( value ) )
    throw makeError( ErrorCode.InvalidEmail );
}

function normalizeDecimalNumber( value, decimal, min = null, max = null, { stripZeros = false } = {} ) {
  const parts = /^(-?\d\d?\d?(?:,\d\d\d)+|-?\d+)(?:\.(\d*))?$/.exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  // strip trailing zeros
  let fractionPart = parts[ 2 ] != null ? parts[ 2 ].replace( /0+$/, '' ) : '';

  if ( fractionPart.length > decimal )
    throw makeError( ErrorCode.TooManyDecimals );

  // strip thousands separators
  let integerPart = parts[ 1 ].replace( /,/g, '' );

  // strip leading zeros
  integerPart = integerPart.replace( /\b0+\B/, '' );

  // change '-0' to '0'
  if ( integerPart == '-0' && fractionPart == '' )
    integerPart = '0';

  const number = fractionPart != '' ? Number( integerPart + '.' + fractionPart ) : Number( integerPart );

  // make sure the number doesn't exceed 14 digits of precision
  if ( Math.abs( number ) >= Math.pow( 10, 14 - decimal ) )
    throw makeError( ErrorCode.TooManyDigits );

  if ( min != null && number < Number( min ) )
    throw makeError( ErrorCode.NumberTooLittle );
  if ( max != null && number > Number( max ) )
    throw makeError( ErrorCode.NumberTooGreat );

  // re-add thousands separators - see https://stackoverflow.com/a/2901298
  integerPart = integerPart.replace( /\B(?=(\d{3})+(?!\d))/g , ',' );

  // re-add trailing zeros if necessary
  if ( decimal > 0 && !stripZeros )
    fractionPart = fractionPart.padEnd( decimal, '0' );

  return fractionPart != '' ? ( integerPart + '.' + fractionPart ) : integerPart;
}

function normalizeDate( value ) {
  const parts = /^(\d\d?)\/(\d\d?)\/(\d\d\d\d)$/.exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  const month = Number( parts[ 1 ] );
  const day = Number( parts[ 2 ] );
  const year = Number( parts[ 3 ] );

  if ( year == 0 )
    throw makeError( ErrorCode.InvalidDate );

  const date = new Date();
  date.setFullYear( year, month - 1, day );

  if ( date.getFullYear() != year || date.getMonth() != month - 1 || date.getDate( day ) != day )
    throw makeError( ErrorCode.InvalidDate );

  return '' + month + '/' + day + '/' + year.toString().padStart( 4, '0' );
}

function normalizeDateTime( value ) {
  const parts = /^(\d\d?)\/(\d\d?)\/(\d\d\d\d)\s+(\d\d?):(\d\d?)\s*([ap]m)$/i.exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  const month = Number( parts[ 1 ] );
  const day = Number( parts[ 2 ] );
  const year = Number( parts[ 3 ] );

  const date = new Date();
  date.setFullYear( year, month - 1, day );

  if ( date.getFullYear() != year || date.getMonth() != month - 1 || date.getDate( day ) != day )
    throw makeError( ErrorCode.InvalidDate );

  const hours = Number( parts[ 4 ] );
  const minutes = Number( parts[ 5 ] );
  const amPm = parts[ 6 ];

  if ( hours < 1 || hours > 12 || minutes > 60 )
    throw makeError( ErrorCode.InvalidTime );

  return '' + month + '/' + day + '/' + year.toString().padStart( 4, '0' ) + ' ' + hours + ':' + minutes.toString().padStart( 2, '0' ) + ' ' + amPm.toLowerCase();
}

function normalizeAttributeValue( value, attribute, project = null, users = [] ) {
  if ( value == '' ) {
    if ( attribute.required == 1 )
      throw makeError( ErrorCode.EmptyValue );
    return '';
  }

  switch ( attribute.type ) {
    case 'TEXT':
      checkLength( value, attribute[ 'min-length' ], attribute[ 'max-length' ] );
      break;

    case 'ENUM':
    case 'USER':
      if ( attribute[ 'multi-select' ] == 1 ) {
        const parts = value.split( ',' ).map( p => p.trim() ).filter( p => p != '' );

        if ( parts.length == 0 ) {
          if ( attribute.required == 1 )
            throw makeError( ErrorCode.EmptyValue );
          return '';
        }

        if ( attribute.type == 'USER' || attribute.editable != 1 ) {
          let items;
          if ( attribute.type == 'ENUM' )
            items = attribute.items;
          else
            items = getUserNames( attribute.members == 1 ? project : null, users );
          parts.forEach( ( part, index ) => {
            if ( !items.includes( part ) )
              throw makeError( ErrorCode.NoMatchingItem );
            if ( parts.includes( part, index + 1 ) )
              throw makeError( ErrorCode.DuplicateItems );
          } );
        }

        value = parts.join( ', ' );
      } else {
        if ( attribute.type == 'USER' || attribute.editable != 1 ) {
          let items;
          if ( attribute.type == 'ENUM' )
            items = attribute.items;
          else
            items = getUserNames( attribute.members == 1 ? project : null, users );
          if ( !items.includes( value ) )
            throw makeError( ErrorCode.NoMatchingItem );
        } else {
          checkLength( value, attribute[ 'min-length' ], attribute[ 'max-length' ] );
        }
      }
      break;

    case 'NUMERIC':
      value = normalizeDecimalNumber( value, attribute.decimal || 0, attribute[ 'min-value' ], attribute[ 'max-value' ], { stripZeros: attribute.strip == 1 } );
      break;

    case 'DATETIME':
      if ( attribute.time == 1 )
        value = normalizeDateTime( value );
      else
        value = normalizeDate( value );
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
    let date = new Date();
    const offset = value.substr( 7 );
    if ( offset != '' )
      date.setDate( date.getDate() + Number( offset ) );
    let formatted = '' + ( date.getMonth() + 1 ) + '/' + date.getDate() + '/' + date.getFullYear().toString().padStart( 4, '0' );
    if ( attribute.time == 1 )
      formatted += ' ' + ( ( date.getHours() + 11 ) % 12 + 1 ) + ':' + date.getMinutes().toString().padStart( 2, '0' ) + ' ' + ( date.getHours() >= 12 ? 'pm' : 'am' );
    return formatted;
  }

  return value;
}

function checkLength( value, minLength, maxLength ) {
  if ( minLength != null && value.length < minLength )
    throw makeError( ErrorCode.StringTooShort );
  if ( maxLength != null && value.length > maxLength )
    throw makeError( ErrorCode.StringTooLong );
}

function getUserNames( project, users ) {
  if ( project != null )
    return users.filter( u => project.members.includes( u.id ) ).map( u => u.name );
  else
    return users.map( u => u.name );
}

function makeError( errorCode ) {
  const error = new Error( 'Parse error: ' + errorCode );
  error.reason = 'APIError';
  error.errorCode = errorCode;
  return error;
}
