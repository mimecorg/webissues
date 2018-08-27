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

import { makeDateString, makeTimeString } from '@/services/formatter'

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.parser )
      this.$parser = options.parser;
    else if ( options.parent && options.parent.$parser )
      this.$parser = options.parent.$parser;
  }
} );

export default function makeParser( store ) {
  return {
    normalizeString,
    parseInteger,
    checkEmailAddress,
    normalizeDecimalNumber( value, decimal, min = null, max = null, flags = {} ) {
      return normalizeDecimalNumber( value, decimal, min, max, flags, store.state.global.settings );
    },
    normalizeDate( value ) {
      return normalizeDate( value, store.state.global.settings );
    },
    normalizeDateTime( value ) {
      return normalizeDateTime( value, store.state.global.settings );
    },
    normalizeAttributeValue( value, attribute, project = null ) {
      return normalizeAttributeValue( value, attribute, project, store.state.global.users, store.state.global.settings );
    },
    parseDate( value, flags = {} ) {
      return parseDate( value, flags, store.state.global.settings );
    }
  };
}

export function makeClientParser() {
  return {
    normalizeString
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

function normalizeDecimalNumber( value, decimal, min, max, { stripZeros = false }, { groupSeparator, decimalSeparator } ) {
  let pattern;
  if ( groupSeparator != '' )
    pattern = '^(-?\\d\\d?\\d?(?:' + escape( groupSeparator ) + '\\d\\d\\d)+|-?\\d+)(?:' + escape( decimalSeparator ) + '(\\d*))?$';
  else
    pattern = '^(-?\\d+)(?:' + escape( decimalSeparator ) + '(\\d*))?$';
  const parts = new RegExp( pattern ).exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  // strip trailing zeros
  let fractionPart = parts[ 2 ] != null ? parts[ 2 ].replace( /0+$/, '' ) : '';

  if ( fractionPart.length > decimal )
    throw makeError( ErrorCode.TooManyDecimals );

  // strip thousands separators
  let integerPart = parts[ 1 ];
  if ( groupSeparator != '' )
    integerPart = integerPart.replace( new RegExp( escape( groupSeparator ), 'g' ), '' );

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
  if ( groupSeparator != '' )
    integerPart = integerPart.replace( /\B(?=(\d{3})+(?!\d))/g , groupSeparator );

  // re-add trailing zeros if necessary
  if ( decimal > 0 && !stripZeros )
    fractionPart = fractionPart.padEnd( decimal, '0' );

  return fractionPart != '' ? ( integerPart + decimalSeparator + fractionPart ) : integerPart;
}

function normalizeDate( value, { dateOrder, dateSeparator, padMonth, padDay } ) {
  const pattern = '^' + makeDatePattern( dateOrder, dateSeparator ) + '$';

  const parts = new RegExp( pattern ).exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  const { year, month, day } = getDateParts( parts, dateOrder );

  if ( !isValidDate( year, month, day ) )
    throw makeError( ErrorCode.InvalidDate );

  return makeDateString( year, month, day, dateOrder, dateSeparator, padMonth, padDay );
}

function normalizeDateTime( value, { dateOrder, dateSeparator, padMonth, padDay, timeMode, timeSeparator, padHours } ) {
  const pattern = '^' + makeDatePattern( dateOrder, dateSeparator ) + '\\s+' + makeTimePattern( timeMode, timeSeparator ) + '$';

  const parts = new RegExp( pattern, 'i' ).exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  const { year, month, day } = getDateParts( parts, dateOrder );

  if ( !isValidDate( year, month, day ) )
    throw makeError( ErrorCode.InvalidDate );

  const hours = Number( parts[ 4 ] );
  const minutes = Number( parts[ 5 ] );
  const amPm = timeMode == 12 ? ' ' + parts[ 6 ].toLowerCase() : '';

  if ( timeMode == 12 ) {
    if ( hours < 1 || hours > 12 || minutes > 60 )
      throw makeError( ErrorCode.InvalidTime );
  } else {
    if ( hours > 23 || minutes > 60 )
      throw makeError( ErrorCode.InvalidTime );
  }

  return makeDateString( year, month, day, dateOrder, dateSeparator, padMonth, padDay ) + ' ' + makeTimeString( hours, minutes, amPm, timeSeparator, padHours );
}

function normalizeAttributeValue( value, attribute, project, users, settings ) {
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
      value = normalizeDecimalNumber( value, attribute.decimal || 0, attribute[ 'min-value' ], attribute[ 'max-value' ], { stripZeros: attribute.strip == 1 }, settings );
      break;

    case 'DATETIME':
      if ( attribute.time == 1 )
        value = normalizeDateTime( value, settings );
      else
        value = normalizeDate( value, settings );
      break;

    default:
      throw makeError( ErrorCode.InvalidDefinition );
  }

  return value;
}

function parseDate( value, { withTime = false }, { dateOrder, dateSeparator, timeMode, timeSeparator } ) {
  let pattern = '^\\s*' + makeDatePattern( dateOrder, dateSeparator )
  if ( withTime )
    pattern += '(?:\\s+' + makeTimePattern( timeMode, timeSeparator ) + ')?';
  pattern += '\\s*$';
  const parts = new RegExp( pattern, 'i' ).exec( value );
  if ( parts != null ) {
    let { year, month, day } = getDateParts( parts, dateOrder );
    const date = new Date();
    date.setFullYear( year, month - 1, day );
    if ( year != 0 && date.getFullYear() == year && date.getMonth() == month - 1 && date.getDate( day ) == day ) {
      if ( withTime && parts[ 4 ] != null ) {
        let hours = Number( parts[ 4 ] );
        const minutes = Number( parts[ 5 ] );
        if ( timeMode == 12 ) {
          if ( hours >= 1 && hours <= 12 && minutes <= 60 ) {
            if ( hours == 12 )
              hours = 0;
            if ( parts[ 6 ].toLowerCase() == 'pm' )
              hours += 12;
            date.setHours( hours, minutes, 0, 0 );
            return date;
          }
        } else {
          if ( hours <= 23 && minutes <= 60 ) {
            date.setHours( hours, minutes, 0, 0 );
            return date;
          }
        }
      } else {
        return date;
      }
    }
  }
  return null;
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

function makeDatePattern( dateOrder, dateSeparator ) {
  dateSeparator = escape( dateSeparator );
  if ( dateOrder.charAt( 0 ) == 'y' )
    return '(\\d\\d\\d\\d?)' + dateSeparator + '(\\d\\d?)' + dateSeparator + '(\\d\\d)';
  else
    return '(\\d\\d?)' + dateSeparator + '(\\d\\d?)' + dateSeparator + '(\\d\\d\\d\\d)';
}

function makeTimePattern( timeMode, timeSeparator ) {
  timeSeparator = escape( timeSeparator );
  if ( timeMode == 12 )
    return '(\\d\\d?)' + timeSeparator + '(\\d\\d?)\\s*([ap]m)';
  else
    return '(\\d\\d?)' + timeSeparator + '(\\d\\d?)';
}

function getDateParts( parts, dateOrder ) {
  let year, month, day;
  for ( let i = 0; i < 3; i++ ) {
    const ch = dateOrder.charAt( i );
    const value = Number( parts[ i + 1 ] );
    if ( ch == 'y' )
      year = value;
    else if ( ch == 'm' )
      month = value;
    else if ( ch == 'd' )
      day = value;
  }
  return { year, month, day };
}

function isValidDate( year, month, day ) {
  if ( year < 1 || year > 9999 )
    return false;
  const date = new Date();
  date.setFullYear( year, month - 1, day );
  if ( date.getFullYear() != year || date.getMonth() != month - 1 || date.getDate( day ) != day )
    return false;
  return true;
}

function escape( string ) {
  return string.replace( /([.+*?=^!:${}()[\]|/\\])/g, '\\$1' );
}

function makeError( errorCode ) {
  const error = new Error( 'Parse error: ' + errorCode );
  error.reason = 'APIError';
  error.errorCode = errorCode;
  return error;
}
