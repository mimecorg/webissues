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

import { expect } from 'chai'

import makeFormatter from '@/services/formatter'

import { invariantSettings } from '@/services/locale'

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

const i18n = {
  t( string, [ value ] ) {
    return string + ' ' + value;
  }
};

const formatter = makeFormatter( { state: { global: { settings: invariantSettings } } }, i18n );
const enFormatter = makeFormatter( { state: { global: { settings: enSettings } } }, i18n );
const plFormatter = makeFormatter( { state: { global: { settings: plSettings } } }, i18n );

describe( 'formatter', () => {
  describe( 'formatDate', () => {
    it( 'invariant date', () => {
      const value = formatter.formatDate( new Date( 2018, 3, 19 ) );
      expect( value ).to.equal( '2018-04-19' );
    } );

    it( 'en date', () => {
      const value = enFormatter.formatDate( new Date( 2018, 3, 19 ) );
      expect( value ).to.equal( '4/19/2018' );
    } );

    it( 'pl date', () => {
      const value = plFormatter.formatDate( new Date( 2018, 3, 19 ) );
      expect( value ).to.equal( '19.04.2018' );
    } );

    it( 'invariant date & time', () => {
      const value = formatter.formatDate( new Date( 2018, 3, 19, 9, 15 ), { withTime: true } );
      expect( value ).to.equal( '2018-04-19 09:15' );
    } );

    it( 'en date & time am', () => {
      const value = enFormatter.formatDate( new Date( 2018, 3, 19, 9, 15 ), { withTime: true } );
      expect( value ).to.equal( '4/19/2018 9:15 am' );
    } );

    it( 'en date & time pm', () => {
      const value = enFormatter.formatDate( new Date( 2018, 3, 19, 21, 15 ), { withTime: true } );
      expect( value ).to.equal( '4/19/2018 9:15 pm' );
    } );

    it( 'pl date & time', () => {
      const value = plFormatter.formatDate( new Date( 2018, 3, 19, 21, 15 ), { withTime: true } );
      expect( value ).to.equal( '19.04.2018 21:15' );
    } );

    it( 'UTC time', () => {
      const value = formatter.formatDate( new Date( Date.UTC( 2018, 3, 19, 21, 15 ) ), { withTime: true, toUTC: true } );
      expect( value ).to.equal( '2018-04-19 21:15' );
    } );

    describe( 'date edge cases', () => {
      it( '0001-01-01', () => {
        const date = new Date();
        date.setFullYear( 1, 0, 1 );
        date.setHours( 0, 0, 0, 0 );
        const value = formatter.formatDate( date );
        expect( value ).to.equal( '0001-01-01' );
      } );

      it( '9999-12-31', () => {
        const value = formatter.formatDate( new Date( 9999, 11, 31 ) );
        expect( value ).to.equal( '9999-12-31' );
      } );
    } );

    describe( 'time edge cases', () => {
      it( '00:00', () => {
        const value = formatter.formatDate( new Date( 2018, 3, 19, 0, 0 ), { withTime: true } );
        expect( value ).to.equal( '2018-04-19 00:00' );
      } );

      it( '23:59', () => {
        const value = formatter.formatDate( new Date( 2018, 3, 19, 23, 59 ), { withTime: true } );
        expect( value ).to.equal( '2018-04-19 23:59' );
      } );

      it( '12:00 am', () => {
        const value = enFormatter.formatDate( new Date( 2018, 3, 19, 0, 0 ), { withTime: true } );
        expect( value ).to.equal( '4/19/2018 12:00 am' );
      } );

      it( '12:00 pm', () => {
        const value = enFormatter.formatDate( new Date( 2018, 3, 19, 12, 0 ), { withTime: true } );
        expect( value ).to.equal( '4/19/2018 12:00 pm' );
      } );
    } );
  } );
} );
