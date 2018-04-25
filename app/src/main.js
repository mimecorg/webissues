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

const { app, BrowserWindow, ipcMain } = require( 'electron' );

const fs = require( 'fs' );
const path = require( 'path' );
const url = require( 'url' );

const dataPath = initializeDataPath();

const config = {
  settings: {
    baseURL: null
  }
};

let mainWindow = null;

app.on( 'ready', () => {
  loadConfiguraton( () => {
    createWindow();
  } );
} );

app.on( 'window-all-closed', () => {
  saveConfiguration( () => {
    if ( process.platform != 'darwin' )
      app.quit();
  } );
} );

app.on( 'activate', () => {
  if ( mainWindow == null )
    createWindow();
} );

ipcMain.on( 'save-settings', ( event, arg ) => {
  config.settings = arg;
  saveConfiguration( () => {} );
} );

ipcMain.on( 'restart-client', ( event, arg ) => {
  config.settings = arg;
  saveConfiguration( () => {
    event.sender.session.clearStorageData( { storages: [ 'cookies' ] }, () => {
      event.sender.send( 'start-client', config.settings );
    } );
  } );
} );

function createWindow() {
  mainWindow = new BrowserWindow( { width: 1280, height: 800 } );

  let pathname;
  if ( process.env.NODE_ENV == 'production' )
    pathname = path.join( __dirname, '../../index.html' );
  else
    pathname = path.join( __dirname, '../index-dev.html' );

  mainWindow.loadURL( url.format( { pathname, protocol: 'file:', slashes: true } ) );

  mainWindow.webContents.on( 'did-finish-load', () => {
    mainWindow.webContents.send( 'start-client', config.settings );
  } );

  mainWindow.on( 'closed', () => {
    mainWindow = null;
  } );
}

function initializeDataPath() {
  let dataPath;

  if ( process.platform == 'win32' )
    dataPath = path.join( process.env.LOCALAPPDATA, 'WebIssues Client\\2.0' )
  else
    dataPath = path.join( app.getPath( 'appData' ), 'webissues-2.0' );

  app.setPath( 'userData', path.join( dataPath, 'user' ) );

  return dataPath;
}

function loadConfiguraton( callback ) {
  loadJSON( path.join( dataPath, 'config.json' ), ( error, data ) => {
    if ( error == null && data != null )
      mergeConfig( config, data );

    callback( error, config );
  } );
}

function saveConfiguration( callback ) {
  saveJSON( path.join( dataPath, 'config.json' ), config, callback );
}

function loadJSON( filePath, callback ) {
  fs.readFile( filePath, 'utf8', ( error, body ) => {
    if ( error != null )
      return callback( error, null );

    let result;
    try {
      result = JSON.parse( body );
    } catch ( error2 ) {
      return callback( error2, null );
    }

    callback( null, result );
  } );
}

function saveJSON( filePath, data, callback ) {
  const body = JSON.stringify( data, null, 2 );

  fs.writeFile( filePath, body, { encoding: 'utf8' }, callback );
}

function mergeConfig( target, source ) {
  for ( const key in source ) {
    if ( target[ key ] != null && typeof target[ key ] == 'object' && source[ key ] != null && typeof source[ key ] == 'object' )
      mergeConfig( target[ key ], source[ key ] );
    else
      target[ key ] = source[ key ];
  }
}
