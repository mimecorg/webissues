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
import { enSettings, plSettings, i18n } from '@/test'

const formatter = makeFormatter( { state: { global: { settings: invariantSettings } } }, i18n );
const enFormatter = makeFormatter( { state: { global: { settings: enSettings } } }, i18n );
const plFormatter = makeFormatter( { state: { global: { settings: plSettings } } }, i18n );

describe( 'formatter', () => {
  describe( 'escape', () => {
    it( 'HTML entities', () => {
      const value = formatter.escape( '"foo" & <bar>' );
      expect( value ).to.equal( '&quot;foo&quot; &amp; &lt;bar&gt;' );
    } );
  } );

  describe( 'convertLinks', () => {
    it( 'www link', () => {
      const value = formatter.convertLinks( 'this www.foo.bar/test.html link' );
      expect( value ).to.equal( 'this <a href="http://www.foo.bar/test.html" target="_blank" rel="noopener noreferrer">www.foo.bar/test.html</a> link' );
    } );

    it( 'ftp link', () => {
      const value = formatter.convertLinks( 'this ftp.foo.bar/test.html link' );
      expect( value ).to.equal( 'this <a href="ftp://ftp.foo.bar/test.html" target="_blank" rel="noopener noreferrer">ftp.foo.bar/test.html</a> link' );
    } );

    it( 'mail link', () => {
      const value = formatter.convertLinks( 'this foo@test.org link' );
      expect( value ).to.equal( 'this <a href="mailto:foo@test.org" target="_blank" rel="noopener noreferrer">foo@test.org</a> link' );
    } );

    it( 'file link', () => {
      const value = formatter.convertLinks( 'this \\\\foo\\test.html link' );
      expect( value ).to.equal( 'this <a href="file:///\\\\foo\\test.html" target="_blank" rel="noopener noreferrer">\\\\foo\\test.html</a> link' );
    } );

    it( 'item link', () => {
      const value = formatter.convertLinks( 'this #123 link' );
      expect( value ).to.equal( 'this <a href="#/items/123">#123</a> link' );
    } );

    it( 'link with parentheses', () => {
      const value = formatter.convertLinks( 'this (www.foo.bar/test_(foo).html) link' );
      expect( value ).to.equal( 'this (<a href="http://www.foo.bar/test_(foo).html" target="_blank" rel="noopener noreferrer">www.foo.bar/test_(foo).html</a>) link' );
    } );

    it( 'HTML entities', () => {
      const value = formatter.convertLinks( '"#1" & <#2>' );
      expect( value ).to.equal( '&quot;<a href="#/items/1">#1</a>&quot; &amp; &lt;<a href="#/items/2">#2</a>&gt;' );
    } );

    it( '& in URL', () => {
      const value = formatter.convertLinks( 'http://foo.bar/a=1&b=2' );
      expect( value ).to.equal( '<a href="http://foo.bar/a=1&amp;b=2" target="_blank" rel="noopener noreferrer">http://foo.bar/a=1&amp;b=2</a>' );
    } );

    describe( 'valid URLs', () => {
      [ 'http://foo.bar', 'https://foo.bar/foo/test.html?a=1', 'ftp://user:pwd@foo/test.html', 'file:///foo/test.html', 'mailto:foo@test.org' ].forEach( url => {
        it( url, () => {
          const value = formatter.convertLinks( url );
          expect( value ).to.equal( '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>' );
        } );
      } );
    } );

    describe( 'invalid URLs', () => {
      [ 'http:foo', 'test://foo.bar/foo/test.html?a=1', 'file://', 'mailto:test.org', '#1b' ].forEach( url => {
        it( url, () => {
          const value = formatter.convertLinks( url );
          expect( value ).to.equal( url );
        } );
      } );
    } );
  } );

  describe( 'convertAttributeValue', () => {
    it( 'single line', () => {
      const value = formatter.convertAttributeValue( '  foo\n  bar', { type: 'TEXT' } );
      expect( value ).to.equal( 'foo bar' );
    } );

    it( 'numeric', () => {
      const value = enFormatter.convertAttributeValue( '-1234.560', { type: 'NUMERIC', decimal: 3, strip: 1 } );
      expect( value ).to.equal( '-1,234.56' );
    } );

    it( 'datetime', () => {
      const value = enFormatter.convertAttributeValue( '1982-04-19 21:45', { type: 'DATETIME', time: 1 } );
      expect( value ).to.equal( '4/19/1982 9:45 pm' );
    } );
  } );

  describe( 'convertInitialValue', () => {
    it( 'me', () => {
      const userFormatter = makeFormatter( { state: { global: { settings: invariantSettings, userName: 'foo bar' } } }, i18n );
      const value = userFormatter.convertInitialValue( '[Me]', { type: 'TEXT' } );
      expect( value ).to.equal( 'foo bar' );
    } );

    it( 'today', () => {
      const value = enFormatter.convertInitialValue( '[Today]', { type: 'DATETIME' } );
      const date = new Date();
      const expected = formatter.formatDate( date );
      expect( value ).to.equal( expected );
    } );

    it( 'today with offset', () => {
      const value = enFormatter.convertInitialValue( '[Today]+3', { type: 'DATETIME' } );
      const date = new Date();
      date.setDate( date.getDate() + 3 );
      const expected = formatter.formatDate( date );
      expect( value ).to.equal( expected );
    } );
  } );

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

  describe( 'formatStamp', () => {
    it( 'stamp', () => {
      const utc = Date.UTC( 2018, 3, 19, 9, 15 );
      const stamp = Math.floor( utc / 1000 );
      const value = formatter.formatStamp( stamp );
      const expected = formatter.formatDate( new Date( utc ), { withTime: true } );
      expect( value ).to.equal( expected );
    } );
  } );

  describe( 'formatFileSize', () => {
    it( 'bytes', () => {
      const value = formatter.formatFileSize( 123 );
      expect( value ).to.equal( '123 FileSize.Bytes' );
    } );

    it( 'kilobytes', () => {
      const value = formatter.formatFileSize( 1024 );
      expect( value ).to.equal( '1 FileSize.Kilobytes' );
    } );

    it( 'megabytes', () => {
      const value = formatter.formatFileSize( 5000000 );
      expect( value ).to.equal( '4.8 FileSize.Megabytes' );
    } );
  } );
} );
