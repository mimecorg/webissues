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
