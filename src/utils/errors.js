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

import { Reason } from '@/constants'

export function makeError( errorCode ) {
  const error = new Error( 'Unexpected error: ' + errorCode );
  error.reason = Reason.APIError;
  error.errorCode = errorCode;
  return error;
}

export function makeParseError( message ) {
  const error = new Error( message );
  error.reason = Reason.ParseError;
  return error;
}

export function makeRouteError( path ) {
  const error = new Error( 'No matching route for path: ' + path );
  error.reason = Reason.PageNotFound;
  return error;
}

export function makeServerError( errorCode, errorMessage, response ) {
  const error = new Error( 'Server returned an error: ' + errorCode + ' (' + errorMessage + ')' );
  if ( errorCode >= 500 )
    error.reason = Reason.ServerError;
  else if ( errorCode >= 400 )
    error.reason = Reason.BadRequest;
  else
    error.reason = Reason.APIError;
  error.errorCode = errorCode;
  error.errorMessage = errorMessage;
  error.response = response;
  return error;
}

export function makeHttpError( response ) {
  const error = new Error( 'HTTP error: ' + response.status + ' (' + response.statusText + ')' );
  if ( response.status >= 500 )
    error.reason = Reason.ServerError;
  else
    error.reason = Reason.BadRequest;
  error.response = response;
  return error;
}

export function makeVersionError( version ) {
  const error = new Error( 'Unsupported server version: ' + version );
  error.reason = Reason.UnsupportedVersion;
  throw error;
}
