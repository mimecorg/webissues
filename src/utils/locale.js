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

import { ErrorCode } from '@/constants'
import { makeError } from '@/utils/errors'

export const invariantSettings = {
  groupSeparator: '',
  decimalSeparator: '.',
  dateOrder: 'ymd',
  dateSeparator: '-',
  padMonth: true,
  padDay: true,
  timeMode: 24,
  timeSeparator: ':',
  padHours: true
};

export function parseDecimalNumber( value, decimal, min, max, { groupSeparator, decimalSeparator } ) {
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

  if ( decimal != null && fractionPart.length > decimal )
    throw makeError( ErrorCode.TooManyDecimals );

  // strip thousands separators
  let integerPart = parts[ 1 ];
  if ( groupSeparator != '' )
    integerPart = integerPart.replace( new RegExp( escape( groupSeparator ), 'g' ), '' );

  const number = fractionPart != '' ? Number( integerPart + '.' + fractionPart ) : Number( integerPart );

  // make sure the number doesn't exceed 14 digits of precision
  if ( decimal != null && Math.abs( number ) >= Math.pow( 10, 14 - decimal ) )
    throw makeError( ErrorCode.TooManyDigits );

  if ( min != null && number < Number( min ) )
    throw makeError( ErrorCode.NumberTooLittle );
  if ( max != null && number > Number( max ) )
    throw makeError( ErrorCode.NumberTooGreat );

  return number;
}

export function formatDecimalNumber( number, decimal, { stripZeros = false }, { groupSeparator, decimalSeparator } ) {
  const fixed = number.toFixed( decimal );

  let [ integerPart, fractionPart = '' ] = fixed.split( '.' );

  // add thousands separators - see https://stackoverflow.com/a/2901298
  if ( groupSeparator != '' )
    integerPart = integerPart.replace( /\B(?=(\d{3})+(?!\d))/g , groupSeparator );

  if ( stripZeros )
    fractionPart = fractionPart.replace( /0+$/, '' );

  // change '-0' to '0'
  if ( integerPart == '-0' && fractionPart == '' )
    integerPart = '0';

  return fractionPart != '' ? ( integerPart + decimalSeparator + fractionPart ) : integerPart;
}

export function parseDate( value, { withTime = false, fromUTC = false }, { dateOrder, dateSeparator, timeMode, timeSeparator } ) {
  let pattern = '^' + makeDatePattern( dateOrder, dateSeparator );
  if ( withTime )
    pattern += '(?: ' + makeTimePattern( timeMode, timeSeparator ) + ')?';
  pattern += '$';

  const parts = new RegExp( pattern, 'i' ).exec( value );
  if ( parts == null )
    throw makeError( ErrorCode.InvalidFormat );

  const { year, month, day } = getDateParts( parts, dateOrder );

  if ( year < 1 || year > 9999 )
    throw makeError( ErrorCode.InvalidDate );

  const date = new Date();
  if ( fromUTC ) {
    date.setUTCFullYear( year, month - 1, day );
    if ( date.getUTCFullYear() != year || date.getUTCMonth() != month - 1 || date.getUTCDate( day ) != day )
      throw makeError( ErrorCode.InvalidDate );
  } else {
    date.setFullYear( year, month - 1, day );
    if ( date.getFullYear() != year || date.getMonth() != month - 1 || date.getDate( day ) != day )
      throw makeError( ErrorCode.InvalidDate );
  }

  let hours = 0, minutes = 0;

  if ( withTime && parts[ 4 ] != null ) {
    hours = Number( parts[ 4 ] );
    minutes = Number( parts[ 5 ] );

    if ( timeMode == 12 ) {
      if ( hours < 1 || hours > 12 || minutes > 60 )
        throw makeError( ErrorCode.InvalidTime );
      if ( hours == 12 )
        hours = 0;
      if ( parts[ 6 ].toLowerCase() == 'pm' )
        hours += 12;
    } else {
      if ( hours > 23 || minutes > 60 )
        throw makeError( ErrorCode.InvalidTime );
    }
  }

  if ( fromUTC )
    date.setUTCHours( hours, minutes, 0, 0 );
  else
    date.setHours( hours, minutes, 0, 0 );

  return date;
}

export function formatDate( date, { withTime = false, toUTC = false }, { dateOrder, dateSeparator, padMonth, padDay, timeMode, timeSeparator, padHours } ) {
  let year = toUTC ? date.getUTCFullYear() : date.getFullYear();
  let month = toUTC ? date.getUTCMonth() + 1 : date.getMonth() + 1;
  let day = toUTC ? date.getUTCDate() : date.getDate();

  year = year.toString().padStart( 4, '0' );
  if ( padMonth )
    month = month.toString().padStart( 2, '0' );
  if ( padDay )
    day = day.toString().padStart( 2, '0' );

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

  let value = parts.join( dateSeparator );

  if ( withTime ) {
    let hours = toUTC ? date.getUTCHours() : date.getHours();
    let minutes = toUTC ? date.getUTCMinutes() : date.getMinutes();
    let amPm = '';

    if ( timeMode == 12 ) {
      amPm = hours >= 12 ? ' pm' :' am';
      hours = ( hours + 11 ) % 12 + 1;
    }

    if ( padHours )
      hours = hours.toString().padStart( 2, '0' );
    minutes = minutes.toString().padStart( 2, '0' );

    value += ' ' + hours + timeSeparator + minutes + amPm;
  }

  return value;
}

function makeDatePattern( dateOrder, dateSeparator ) {
  dateSeparator = escape( dateSeparator );
  if ( dateOrder.charAt( 0 ) == 'y' )
    return '(\\d\\d\\d\\d)' + dateSeparator + '(\\d\\d?)' + dateSeparator + '(\\d\\d?)';
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

function escape( string ) {
  return string.replace( /([.+*?=^!:${}()[\]|/\\])/g, '\\$1' );
}
