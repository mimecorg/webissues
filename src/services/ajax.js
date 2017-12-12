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
    post( url, data = {} ) {
      return new Promise( ( resolve, reject ) => {
        fetch( baseURL + url, {
          method: 'POST',
          body: JSON.stringify( data ),
          headers: makeHeaders( csrfToken ),
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
              error.reason = 'invalid_response';
              reject( error );
            }
          } );
        } ).catch( error => {
          error.reason = 'network_error';
          reject( error );
        } );
      } );
    }
  };
}

function makeHeaders( csrfToken ) {
  const headers = {
    'Content-Type': 'application/json',
  };
  if ( csrfToken != null )
    headers[ 'X-CSRF-Token' ] = csrfToken;
  return headers;
}

function makeError( errorCode, errorMessage, response ) {
  const error = new Error( 'Server returned an error: ' + errorCode + ' (' + errorMessage + ')' );
  if ( errorCode >= 500 )
    error.reason = 'server_error';
  else if ( errorCode >= 400 )
    error.reason = 'bad_request';
  else
    error.reason = 'api_error';
  error.errorCode = errorCode;
  error.errorMessage = errorMessage;
  error.response = response;
  return error;
}

function makeHttpError( response ) {
  const error = new Error( 'HTTP error: ' + response.status + ' (' + response.statusText + ')' );
  if ( response.status >= 500 )
    error.reason = 'server_error';
  else
    error.reason = 'bad_request';
  error.response = response;
  return error;
}
