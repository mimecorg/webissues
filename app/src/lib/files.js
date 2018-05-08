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

const { app } = require( 'electron' );

const fs = require( 'fs' );
const path = require( 'path' );

const dataPath = initializeDataPath();

function loadJSON( filePath, callback ) {
  loadText( filePath, ( error, text ) => {
    if ( error != null )
      return callback( error, null );

    let result;
    try {
      result = JSON.parse( text );
    } catch ( error ) {
      return callback( error, null );
    }

    callback( null, result );
  } );
}

function saveJSON( filePath, data, callback ) {
  const text = JSON.stringify( data, null, 2 );

  saveText( filePath, text, callback );
}

function loadText( filePath, callback ) {
  fs.open( filePath + '.bak', 'r', ( error, fd ) => {
    if ( error != null && error.code != 'ENOENT' )
      return callback( error, null );

    if ( error == null ) {
      fs.close( fd, error => {
        if ( error != null )
          return callback( error, null );

        fs.unlink( filePath, error => {
          if ( error != null && error.code != 'ENOENT' )
            return callback( error, null );

          fs.rename( filePath + '.bak', filePath, error => {
            if ( error != null )
              return callback( error, null );

            fs.readFile( filePath, 'utf8', callback );
          } );
        } );
      } );
    } else {
      fs.readFile( filePath, 'utf8', callback );
    }
  } );
}

function saveText( filePath, text, callback ) {
  fs.rename( filePath, filePath + '.bak', error => {
    if ( error != null && error.code != 'ENOENT' )
      return callback( error );

    fs.writeFile( filePath, text, { encoding: 'utf8' }, error => {
      if ( error != null )
        return callback( error );

      fs.unlink( filePath + '.bak', callback );
    } );
  } );
}

function initializeDataPath() {
  let dataPath;

  if ( process.platform == 'win32' )
    dataPath = path.join( process.env.LOCALAPPDATA, 'WebIssues Client\\2.0' )
  else
    dataPath = path.join( app.getPath( 'appData' ), 'webissues-2.0' );

  app.setPath( 'userData', path.join( dataPath, 'browser' ) );

  return dataPath;
}

module.exports = {
  dataPath,
  loadJSON,
  saveJSON,
  loadText,
  saveText
};
