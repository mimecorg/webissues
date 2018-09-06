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

import { Reason } from '@/constants'
import { makeServerError, makeHttpError } from '@/utils/errors'

Vue.mixin( {
  beforeCreate() {
    const options = this.$options;
    if ( options.ajax )
      this.$ajax = options.ajax;
    else if ( options.parent && options.parent.$ajax )
      this.$ajax = options.parent.$ajax;
  }
} );

export default function makeAjax( baseURL, csrfToken ) {
  return {
    post( url, data = {}, file = null ) {
      return new Promise( ( resolve, reject ) => {
        let body;
        const headers = {};
        if ( file != null ) {
          body = new FormData();
          body.append( 'data', JSON.stringify( data ) );
          body.append( 'file', file, file.name );
        } else {
          body = JSON.stringify( data );
          headers[ 'Content-Type' ] = 'application/json';
        }
        if ( csrfToken != null )
          headers[ 'X-CSRF-Token' ] = csrfToken;
        fetch( baseURL + url, {
          method: 'POST',
          body,
          headers,
          credentials: process.env.TARGET == 'web' ? 'same-origin' : 'include'
        } ).then( response => {
          response.json().then( ( { result, errorCode, errorMessage } ) => {
            if ( errorCode != null )
              reject( makeServerError( errorCode, errorMessage, response ) );
            else if ( !response.ok )
              reject( makeHttpError( response ) );
            else
              resolve( result );
          } ).catch( error => {
            if ( !response.ok ) {
              reject( makeHttpError( response ) );
            } else {
              error.response = response;
              error.reason = Reason.InvalidResponse;
              reject( error );
            }
          } );
        } ).catch( error => {
          error.reason = Reason.NetworkError;
          reject( error );
        } );
      } );
    },
    withBaseURL( baseURL ) {
      return makeAjax( baseURL, null );
    }
  };
}
