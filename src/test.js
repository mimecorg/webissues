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

import chai from 'chai'
import chaiDatetime from 'chai-datetime'

chai.use( chaiDatetime );

export const enSettings = {
  groupSeparator: ',',
  decimalSeparator: '.',
  dateOrder: 'mdy',
  dateSeparator: '/',
  padMonth: false,
  padDay: false,
  timeMode: 12,
  timeSeparator: ':',
  padHours: false
};

export const plSettings = {
  groupSeparator: ' ',
  decimalSeparator: ',',
  dateOrder: 'dmy',
  dateSeparator: '.',
  padMonth: true,
  padDay: true,
  timeMode: 24,
  timeSeparator: ':',
  padHours: true
};

export const i18n = {
  t( string, args ) {
    if ( args != null )
      return args[ 0 ] + ' ' + string;
    else
      return string;
  }
};
