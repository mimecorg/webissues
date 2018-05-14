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

const { app, dialog, ipcMain } = require( 'electron' );

const fs = require( 'fs' );
const path = require( 'path' );

const { makeCache } = require( './cache' );

const cache = makeCache( 'files', { maxSize: 200 * 1024 * 1024, maxCount: 200 } );

let currentWindow = null;

let currentItem = null;

app.on( 'browser-window-created', ( event, window ) => {
  currentWindow = window;

  window.on( 'closed', () => {
    currentWindow = null;
  } );
} );

ipcMain.on( 'find-attachment', ( event, serverUUID, fileId ) => {
  const record = cache.find( r => r.serverUUID == serverUUID && r.fileId == fileId );

  if ( record == null ) {
    event.sender.send( 'find-attachment-result', null, null );
    return;
  }

  const filePath = cache.getFilePath( record.name );

  fs.access( filePath, error => {
    if ( error != null && error.code == 'ENOENT' ) {
      event.sender.send( 'find-attachment-result', null, null );
    } else if ( error != null ) {
      event.sender.send( 'find-attachment-result', error.message, null );
    } else {
      record.lastAccess = Date.now();

      cache.save( error => {
        event.sender.send( 'find-attachment-result', null, filePath );
      } );
    }
  } );
} );

ipcMain.on( 'download-attachment', ( event, serverUUID, fileId, name, size, url ) => {
  cache.allocateSpace( size, () => {
    generateFileName( name, ( error, generatedName ) => {
      if ( error != null ) {
        event.sender.send( 'download-attachment-result', error.message, null );
        return;
      }

      const filePath = cache.getFilePath( generatedName );

      if ( currentWindow == null ) {
        event.sender.send( 'download-attachment-result', 'Browser window closed', null );
        return;
      }

      currentWindow.webContents.session.once( 'will-download', ( event, item, webContents ) => {
        currentItem = item;

        item.setSavePath( filePath );

        const stateHandler = ( event, state ) => {
          if ( state == 'progressing' ) {
            if ( currentWindow != null )
              currentWindow.webContents.send( 'download-attachment-progress', item.getReceivedBytes() );
          } else if ( state == 'completed' ) {
            currentItem = null;

            cache.push( { serverUUID, fileId, name: generatedName, size, lastAccess: Date.now() } );

            cache.save( error => {
              if ( currentWindow != null )
                currentWindow.webContents.send( 'download-attachment-result', null, filePath );
            } );
          } else {
            currentItem = null;

            if ( currentWindow != null )
              currentWindow.webContents.send( 'download-attachment-result', 'Download failed', null );
          }
        };

        item.on( 'updated', stateHandler );
        item.on( 'done', stateHandler );
      } );

      currentWindow.webContents.downloadURL( url );
    } );
  } );
} );

ipcMain.on( 'abort-attachment', ( event ) => {
  if ( currentItem != null )
    currentItem.cancel();
} );

ipcMain.on( 'save-attachment', ( event, filePath, name ) => {
  dialog.showSaveDialog( currentWindow, { defaultPath: name }, targetPath => {
    if ( targetPath == null ) {
      event.sender.send( 'save-attachment-result', null, null );
      return;
    }

    fs.copyFile( filePath, targetPath, error => {
      if ( error != null )
        event.sender.send( 'save-attachment-result', error.message, null );
      else
        event.sender.send( 'save-attachment-result', null, targetPath );
    } );
  } );
} );

function initializeAttachments( callback ) {
  cache.initialize( callback );
}

function generateFileName( name, callback ) {
  checkAccess( name, 1 );

  function checkAccess( generatedName, counter ) {
    const filePath = cache.getFilePath( generatedName );

    fs.access( filePath, error => {
      if ( error != null && error.code == 'ENOENT' )
        callback( null, generatedName );
      else if ( error != null )
        callback( error, null );
      else
        checkAccess( path.basename( name, path.extname( name ) ) + ' (' + counter + ')' + path.extname( name ), counter + 1 );
    } );
  }
}

module.exports = {
  initializeAttachments
};
