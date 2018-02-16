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
          credentials: 'same-origin'
        } ).then( response => {
          response.json().then( ( { result, errorCode, errorMessage } ) => {
            if ( errorCode != null )
              reject( makeError( errorCode, errorMessage, response ) );
            else if ( !response.ok )
              reject( makeHttpError( response ) );
            else
              resolve( result );
          } ).catch( error => {
            if ( !response.ok ) {
              reject( makeHttpError( response ) );
            } else {
              error.response = response;
              error.reason = 'InvalidResponse';
              reject( error );
            }
          } );
        } ).catch( error => {
          error.reason = 'NetworkError';
          reject( error );
        } );
      } );
    }
  };
}

function makeError( errorCode, errorMessage, response ) {
  const error = new Error( 'Server returned an error: ' + errorCode + ' (' + errorMessage + ')' );
  if ( errorCode >= 500 )
    error.reason = 'ServerError';
  else if ( errorCode >= 400 )
    error.reason = 'BadRequest';
  else
    error.reason = 'APIError';
  error.errorCode = errorCode;
  error.errorMessage = errorMessage;
  error.response = response;
  return error;
}

function makeHttpError( response ) {
  const error = new Error( 'HTTP error: ' + response.status + ' (' + response.statusText + ')' );
  if ( response.status >= 500 )
    error.reason = 'ServerError';
  else
    error.reason = 'BadRequest';
  error.response = response;
  return error;
}
