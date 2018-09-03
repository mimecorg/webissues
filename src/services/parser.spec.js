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

import makeParser from '@/services/parser'

import { ErrorCode } from '@/constants'
import { invariantSettings, makeError } from '@/services/locale'
import { enSettings, plSettings } from '@/test'

const parser = makeParser( { state: { global: { settings: invariantSettings } } } );
const enParser = makeParser( { state: { global: { settings: enSettings } } } );
const plParser = makeParser( { state: { global: { settings: plSettings } } } );

describe( 'parser', () => {
  describe( 'normalizeString', () => {
    it( 'single line', () => {
      const value = parser.normalizeString( '  foo  bar  ' );
      expect( value ).to.equal( 'foo bar' );
    } );

    it( 'multiple lines', () => {
      const value = parser.normalizeString( '  foo\r\n  bar  \n\t  ', null, { multiLine: true } );
      expect( value ).to.equal( '  foo\n  bar' );
    } );

    it( 'allow empty value', () => {
      const value = parser.normalizeString( '  ', null, { allowEmpty: true } );
      expect( value ).to.equal( '' );
    } );

    it( 'maximum length', () => {
      const value = parser.normalizeString( '  foo  bar  ', 7 );
      expect( value ).to.equal( 'foo bar' );
    } );

    describe( 'invalid values', () => {
      it( 'empty value', () => {
        expect( () => parser.normalizeString( '  ' ) ).to.throw( makeError( ErrorCode.EmptyValue ).message );
      } );

      it( 'empty multiline value', () => {
        expect( () => parser.normalizeString( '  \r\n\t  ', null, { multiLine: true } ) ).to.throw( makeError( ErrorCode.EmptyValue ).message );
      } );

      it( 'value too long', () => {
        expect( () => parser.normalizeString( '  foo  bar  ', 6 ) ).to.throw( makeError( ErrorCode.StringTooLong ).message );
      } );

      it( 'invalid characters', () => {
        expect( () => parser.normalizeString( 'foo\nbar' ) ).to.throw( makeError( ErrorCode.InvalidString ).message );
      } );

      it( 'invalid multiline characters', () => {
        expect( () => parser.normalizeString( 'foo\x03bar', null, { multiLine: true } ) ).to.throw( makeError( ErrorCode.InvalidString ).message );
      } );
    } );
  } );

  describe( 'parseInteger', () => {
    describe( 'valid values', () => {
      [ '0', '1', '-123', '-2147483648', '2147483647' ].forEach( value => {
        it( value, () => {
          const number = parser.parseInteger( value );
          expect( number ).to.equal( Number( value ) );
        } );
      } );
    } );

    describe( 'range check', () => {
      [ '1', '10', '100' ].forEach( value => {
        it( value, () => {
          const number = parser.parseInteger( value, 1, 100 );
          expect( number ).to.equal( Number( value ) );
        } );
      } );
    } );

    describe( 'invalid values', () => {
      [ '-', '0.0', '1e3', '0xf', 'abc' ].forEach( value => {
        it( value, () => {
          expect( () => parser.parseInteger( value ) ).to.throw( makeError( ErrorCode.InvalidFormat ).message );
        } );
      } );

      it( '-2147483649', () => {
        expect( () => parser.parseInteger( '-2147483649' ) ).to.throw( makeError( ErrorCode.NumberTooLittle ).message );
      } );

      it( '2147483648', () => {
        expect( () => parser.parseInteger( '2147483648' ) ).to.throw( makeError( ErrorCode.NumberTooGreat ).message );
      } );

      it( 'below range', () => {
        expect( () => parser.parseInteger( '0', 1, 100 ) ).to.throw( makeError( ErrorCode.NumberTooLittle ).message );
      } );

      it( 'above range', () => {
        expect( () => parser.parseInteger( '101', 1, 100 ) ).to.throw( makeError( ErrorCode.NumberTooGreat ).message );
      } );
    } );
  } );

  describe( 'checkEmailAddress', () => {
    describe( 'valid values', () => {
      [ 'foo@test.org', 'foo-bar@test.pl', 'Foo.Bar@test.foo.org', 'foo+bar@test-foo.org' ].forEach( value => {
        it( value, () => {
          parser.checkEmailAddress( value );
        } )
      } );
    } );

    describe( 'invalid values', () => {
      [ 'foo', 'foo@', '@test.org', 'foo@bar', 'foo@bar.org@test.org', 'foo!bar@test.org' ].forEach( value => {
        it( value, () => {
          expect( () => parser.checkEmailAddress( value ) ).to.throw( makeError( ErrorCode.InvalidEmail ).message );
        } );
      } );
    } );
  } );

  describe( 'parseDate', () => {
    it( 'invariant date', () => {
      const date = parser.parseDate( '2018-04-19' );
      expect( date ).to.equalTime( new Date( 2018, 3, 19 ) );
    } );

    it( 'en date', () => {
      const date = enParser.parseDate( '4/19/2018' );
      expect( date ).to.equalTime( new Date( 2018, 3, 19 ) );
    } );

    it( 'pl date', () => {
      const date = plParser.parseDate( '19.04.2018' );
      expect( date ).to.equalTime( new Date( 2018, 3, 19 ) );
    } );

    it( 'invariant date & time', () => {
      const date = parser.parseDate( '2018-04-19 09:15', { withTime: true } );
      expect( date ).to.equalTime( new Date( 2018, 3, 19, 9, 15 ) );
    } );

    it( 'en date & time am', () => {
      const date = enParser.parseDate( '4/19/2018 9:15 am', { withTime: true } );
      expect( date ).to.equalTime( new Date( 2018, 3, 19, 9, 15 ) );
    } );

    it( 'en date & time pm', () => {
      const date = enParser.parseDate( '4/19/2018 9:15 pm', { withTime: true } );
      expect( date ).to.equalTime( new Date( 2018, 3, 19, 21, 15 ) );
    } );

    it( 'pl date & time', () => {
      const date = plParser.parseDate( '19.04.2018 21:15', { withTime: true } );
      expect( date ).to.equalTime( new Date( 2018, 3, 19, 21, 15 ) );
    } );

    it( 'time missing', () => {
      const date = parser.parseDate( '2018-04-19', { withTime: true } );
      expect( date ).to.equalTime( new Date( 2018, 3, 19, 0, 0 ) );
    } );

    it( 'UTC time', () => {
      const date = parser.parseDate( '2018-04-19 21:15', { withTime: true, fromUTC: true } );
      expect( date ).to.equalTime( new Date( Date.UTC( 2018, 3, 19, 21, 15 ) ) );
    } );

    it( 'optional zeros', () => {
      const date = parser.parseDate( '2018-1-1 1:1', { withTime: true } );
      expect( date ).to.equalTime( new Date( 2018, 0, 1, 1, 1 ) );
    } );

    describe( 'date edge cases', () => {
      it( '0001-01-01', () => {
        const date = parser.parseDate( '0001-01-01' );
        const expected = new Date();
        expected.setFullYear( 1, 0, 1 );
        expected.setHours( 0, 0, 0, 0 );
        expect( date ).to.equalTime( expected );
      } );

      it( '9999-12-31', () => {
        const date = parser.parseDate( '9999-12-31' );
        expect( date ).to.equalTime( new Date( 9999, 11, 31 ) );
      } );
    } );

    describe( 'time edge cases', () => {
      it( '00:00', () => {
        const date = parser.parseDate( '2018-04-19 00:00', { withTime: true } );
        expect( date ).to.equalTime( new Date( 2018, 3, 19, 0, 0 ) );
      } );

      it( '23:59', () => {
        const date = parser.parseDate( '2018-04-19 23:59', { withTime: true } );
        expect( date ).to.equalTime( new Date( 2018, 3, 19, 23, 59 ) );
      } );

      it( '12:00 am', () => {
        const date = enParser.parseDate( '4/19/2018 12:00 am', { withTime: true } );
        expect( date ).to.equalTime( new Date( 2018, 3, 19, 0, 0 ) );
      } );

      it( '12:00 pm', () => {
        const date = enParser.parseDate( '4/19/2018 12:00 pm', { withTime: true } );
        expect( date ).to.equalTime( new Date( 2018, 3, 19, 12, 0 ) );
      } );
    } );

    describe( 'invalid format', () => {
      const message = makeError( ErrorCode.InvalidFormat ).message;
      [ '2018-104-19', '2018-04-119', '18-04-19', '19-04-2018', '20180419', '2018/04/19', '2018-04-19 21:15' ].forEach( value => {
        it( value, () => {
          expect( () => parser.parseDate( value ) ).to.throw( message );
        } );
      } );
      [ '2018-04-19 21:15:00', '2018-04-19 9:15 pm', '2018-04-19 21.15', '2018-04-19 121:15', '2018-04-19 21:115' ].forEach( value => {
        it( value, () => {
          expect( () => parser.parseDate( value, { withTime: true } ) ).to.throw( message );
        } );
      } );
    } );

    describe( 'invalid format en', () => {
      const message = makeError( ErrorCode.InvalidFormat ).message;
      [ '104/19/2018', '4/119/2018', '4/19/18', '2018/4/19', '4192018', '4-19-2018', '4/19/2018 9:15 pm' ].forEach( value => {
        it( value, () => {
          expect( () => enParser.parseDate( value ) ).to.throw( message );
        } );
      } );
      [ '4/19/2018 9:15', '4/19/2018 9:15 p', '4/19/2018 9:15:00 pm', '4/19/2018 21.15 pm', '4/19/2018 121:15 pm', '4/19/2018 9:115 pm' ].forEach( value => {
        it( value, () => {
          expect( () => enParser.parseDate( value, { withTime: true } ) ).to.throw( message );
        } );
      } );
    } );

    describe( 'invalid date', () => {
      const message = makeError( ErrorCode.InvalidDate ).message;
      [ '2018-04-35', '2018-13-19', '2018-02-29', '0000-04-19' ].forEach( value => {
        it( value, () => {
          expect( () => parser.parseDate( value ) ).to.throw( message );
        } );
      } );
    } );

    describe( 'invalid time', () => {
      const message = makeError( ErrorCode.InvalidTime ).message;
      [ '2018-04-19 21:75', '2018-04-19 24:00', '2018-04-19 25:15' ].forEach( value => {
        it( value, () => {
          expect( () => parser.parseDate( value, { withTime: true } ) ).to.throw( message );
        } );
      } );
    } );

    describe( 'invalid time en', () => {
      const message = makeError( ErrorCode.InvalidTime ).message;
      [ '4/19/2018 9:75 pm', '4/19/2018 0:15 am', '4/19/2018 13:00 pm' ].forEach( value => {
        it( value, () => {
          expect( () => enParser.parseDate( value, { withTime: true } ) ).to.throw( message );
        } );
      } );
    } );
  } );
} );
