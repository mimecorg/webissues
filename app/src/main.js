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
const path = require( 'path' );
const url = require( 'url' );

let mainWindow = null;

app.on( 'ready', createWindow );

app.on( 'window-all-closed', () => {
  if ( process.platform != 'darwin' )
    app.quit();
} );

app.on( 'activate', () => {
  if ( mainWindow == null )
    createWindow();
} );

ipcMain.on( 'switch-to-client', ( event, arg ) => {
  event.sender.session.clearStorageData( { storages: [ 'cookies' ] }, () => {
    event.sender.send( 'start-client' );
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
    mainWindow.webContents.send( 'start-client' );
  } );

  mainWindow.on( 'closed', () => {
    mainWindow = null;
  } );
}
