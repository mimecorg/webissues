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

export default function makeAjax( baseURL, i18n ) {
  return {
    post( url, data = {} ) {
      return new Promise( ( resolve, reject ) => {
        fetch( baseURL + url, {
          method: 'POST',
          body: JSON.stringify( data ),
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'same-origin'
        } ).then( response => {
          if ( response.status != 204 ) {
            response.json().then( data => {
              if ( response.ok )
                resolve( data );
              else
                reject( convertError( data, i18n ) );
            } ).catch( error => {
              reject( new Error( i18n.t( 'error.invalid_response' ) ) );
            } );
          } else {
            resolve( null );
          }
        } ).catch( error => {
          reject( new Error( i18n.t( 'error.network_error' ) ) );
        } );
      } );
    }
  };
}

function convertError( { errorCode, errorMessage }, i18n ) {
  if ( errorCode >= 500 ) {
    if ( errorCode == 501 || errorCode == 502 )
      return new Error( i18n.t( 'error.server_not_configured' ) );
    else
      return new Error( i18n.t( 'error.server_error' ) );
  } else if ( errorCode >= 400 ) {
    if ( errorCode == 403 )
      return new Error( i18n.t( 'error.upload_error' ) );
    else
      return new Error( i18n.t( 'error.bad_request' ) );
  } else if ( errorCode >= 300 ) {
    let message;
    if ( i18n.te( 'error_code.' + errorCode ) )
      message = i18n.t( 'error_code.' + errorCode );
    else
      message = i18n.t( 'error.unknown_error', [ errorCode, errorMessage ] );
    const apiError = new Error( message );
    apiError.errorCode = errorCode;
    return apiError;
  } else {
    return new Error( i18n.t( 'error.invalid_response' ) );
  }
}
