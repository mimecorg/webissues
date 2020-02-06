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

import { ErrorCode } from '@/constants'
import { makeError } from '@/utils/errors'
import { invariantSettings, parseDecimalNumber, formatDecimalNumber, parseDate, formatDate } from '@/utils/locale'

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.parser )
      this.$parser = options.parser;
    else if ( options.parent && options.parent.$parser )
      this.$parser = options.parent.$parser;
  }
} );

export default function makeParser( store, i18n ) {
  return {
    normalizeString,
    parseInteger,
    checkEmailAddress,
    normalizeAttributeValue( value, attribute, project = null ) {
      return normalizeAttributeValue( value, attribute, project, store.state.global.users, store.state.global.settings );
    },
    convertAttributeValue( value, attribute ) {
      return convertAttributeValue( value, attribute, store.state.global.settings );
    },
    normalizeExpression( value, attribute ) {
      return normalizeExpression( value, attribute, i18n, store.state.global.users, store.state.global.settings );
    },
    convertExpression( value, attribute ) {
      return convertExpression( value, attribute, i18n, store.state.global.settings );
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
      const number = parseDecimalNumber( value, attribute.decimal || 0, attribute[ 'min-value' ], attribute[ 'max-value' ], settings );
      value = formatDecimalNumber( number, attribute.decimal || 0, { stripZeros: attribute.strip == 1 }, settings );
      break;

    case 'DATETIME':
      const date = parseDate( value, { withTime: attribute.time == 1 }, settings );
      value = formatDate( date, { withTime: attribute.time == 1 }, settings );
      break;

    default:
      throw makeError( ErrorCode.InvalidDefinition );
  }

  return value;
}

function convertAttributeValue( value, attribute, settings ) {
  if ( value == '' )
    return '';

  switch ( attribute.type ) {
    case 'TEXT':
    case 'ENUM':
    case 'USER':
      break;

    case 'NUMERIC':
      const number = parseDecimalNumber( value, attribute.decimal || 0, null, null, settings );
      value = formatDecimalNumber( number, attribute.decimal || 0, {}, invariantSettings );
      break;

    case 'DATETIME':
      const date = parseDate( value, { withTime: attribute.time == 1 }, settings );
      value = formatDate( date, { withTime: attribute.time == 1, toUTC: attribute.local == 1 }, invariantSettings );
      break;

    default:
      throw makeError( ErrorCode.InvalidDefinition );
  }

  return value;
}

function normalizeExpression( value, attribute, i18n, users, settings ) {
  if ( value == '' ) {
    if ( attribute.required == 1 )
      throw makeError( ErrorCode.EmptyValue );
    return '';
  }

  const result = processExpression( value, attribute, i18n, true );
  if ( result != null )
    return result;

  return normalizeAttributeValue( value, attribute, null, users, settings );
}

function convertExpression( value, attribute, i18n, settings ) {
  if ( value == '' )
    return '';

  const result = processExpression( value, attribute, i18n, false );
  if ( result != null )
    return result;

  return convertAttributeValue( value, attribute, settings );
}

function processExpression( value, attribute, i18n, normalize ) {
  if ( attribute.type == 'TEXT' || attribute.type == 'ENUM' || attribute.type == 'USER' ) {
    const me = '[' + i18n.t( 'text.Me' ) + ']';
    if ( value.substr( 0, me.length ).toLowerCase() == me.toLowerCase() ) {
      if ( value.length > me.length )
        throw makeError( ErrorCode.InvalidFormat );
      return normalize ? me : '[Me]';
    }
  }

  if ( attribute.type == 'DATETIME' ) {
    const today = '[' + i18n.t( 'text.Today' ) + ']';
    if ( value.substr( 0, today.length ).toLowerCase() == today.toLowerCase() ) {
      let result = normalize ? today : '[Today]';
      if ( value.length > today.length ) {
        const parts = /^\s*([+-])\s*(\d+)$/.exec( value.substr( today.length ) );
        if ( parts == null || parts[ 2 ] == 0 )
          throw makeError( ErrorCode.InvalidFormat );
        result += parts[ 1 ] + parts[ 2 ];
      }
      return result;
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
