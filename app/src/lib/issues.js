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

const { ipcMain } = require( 'electron' );

const fs = require( 'fs' );

const { packJSON, unpackJSON, writeFileSafe } = require( './files' );

const { makeCache } = require( './cache' );

const cache = makeCache( 'issues', { maxSize: 50 * 1024 * 1024, maxCount: 200 } );

ipcMain.on( 'load-issue', ( event, serverUUID, issueId ) => {
  const record = cache.find( r => r.serverUUID == serverUUID && r.issueId == issueId );

  if ( record == null ) {
    event.sender.send( 'load-issue-result', null, null );
    return;
  }

  const filePath = cache.getFilePath( record.name );

  fs.readFile( filePath, ( error, buffer ) => {
    if ( error != null ) {
      event.sender.send( 'load-issue-result', error.message, null );
      return;
    }

    unpackJSON( buffer, ( error, data ) => {
      if ( error != null ) {
        event.sender.send( 'load-issue-result', error.message, null );
        return;
      }

      record.lastAccess = Date.now();

      cache.save( error => {
        event.sender.send( 'load-issue-result', null, data );
      } );
    } );
  } );
} );

ipcMain.on( 'save-issue', ( event, serverUUID, issueId, data ) => {
  packJSON( data, ( error, buffer ) => {
    if ( error != null ) {
      event.sender.send( 'save-issue-result', error.message );
      return;
    }

    const size = buffer.length;

    const record = cache.find( r => r.serverUUID == serverUUID && r.issueId == issueId );

    if ( record != null )
      record.lastAccess = -1;

    cache.allocateSpace( size, () => {
      generateFileName( ( error, name ) => {
        if ( error != null ) {
          event.sender.send( 'save-issue-result', error.message );
          return;
        }

        const filePath = cache.getFilePath( name );

        writeFileSafe( filePath, buffer, null, error => {
          if ( error != null ) {
            event.sender.send( 'save-issue-result', error.message );
            return;
          }

          cache.push( { serverUUID, issueId, name, size, lastAccess: Date.now() } );

          cache.save( error => {
            event.sender.send( 'save-issue-result', null );
          } );
        } );
      } );
    } );
  } );
} );

function initializeIssues( callback ) {
  cache.initialize( callback );
}

function generateFileName( callback ) {
  checkAccess();

  function checkAccess() {
    const number = Math.floor( Math.random() * 0xffffff );
    const name = number.toString( 16 ).padStart( 6, '0' );

    const filePath = cache.getFilePath( name );

    fs.access( filePath, error => {
      if ( error != null && error.code == 'ENOENT' )
        callback( null, name );
      else if ( error != null )
        callback( error, null );
      else
        checkAccess();
    } );
  }
}

module.exports = {
  initializeIssues
};
