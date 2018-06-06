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

const { app, BrowserWindow, ipcMain, shell } = require( 'electron' );

const path = require( 'path' );
const url = require( 'url' );

const { config, loadConfiguraton, saveConfiguration, adjustPosition } = require( './lib/config' );
const { initializeAttachments } = require( './lib/attachments' );
const { makeContextMenuHandler, makeDarwinMenu } = require( './lib/menus' );

let configSaved = false;

let mainWindow = null;

app.on( 'ready', () => {
  loadConfiguraton( () => {
    initializeAttachments( () => {
      createWindow();
    } );
  } );
} );

app.on( 'window-all-closed', () => {
  if ( process.platform != 'darwin' )
    app.quit();
} );

app.on( 'will-quit', event => {
  if ( !configSaved ) {
    event.preventDefault();

    saveConfiguration( () => {
      configSaved = true;
      app.quit();
    } );
  }
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
  const position = config.position;
  adjustPosition( position );

  mainWindow = new BrowserWindow( {
    x: position.x,
    y: position.y,
    width: position.width,
    height: position.height,
    minWidth: 200,
    minHeight: 120,
    show: !position.maximized,
    title: 'WebIssues'
  } );

  if ( position.maximized ) {
    mainWindow.maximize();

    if ( !mainWindow.isVisible() )
      mainWindow.show();
  }

  let pathname;
  if ( process.env.NODE_ENV == 'production' )
    pathname = path.join( __dirname, '../../index.html' );
  else
    pathname = path.join( __dirname, '../index-dev.html' );

  mainWindow.loadURL( url.format( { pathname, protocol: 'file:', slashes: true } ) );

  mainWindow.webContents.on( 'did-finish-load', () => {
    mainWindow.webContents.send( 'start-client', config.settings );
  } );

  mainWindow.webContents.on( 'will-navigate', handleLink );
  mainWindow.webContents.on( 'new-window', handleLink );

  function handleLink( event, url ) {
    if ( url != mainWindow.webContents.getURL() ) {
      event.preventDefault();
      shell.openExternal( url );
    }
  }

  mainWindow.webContents.on( 'context-menu', makeContextMenuHandler( mainWindow ) );

  mainWindow.on( 'resize', handleStateChange );
  mainWindow.on( 'move', handleStateChange );

  function handleStateChange() {
    if ( !mainWindow.isMinimized() && !mainWindow.isFullScreen() ) {
      if ( !mainWindow.isMaximized() ) {
        const bounds = mainWindow.getBounds();
        position.x = bounds.x;
        position.y = bounds.y;
        position.width = bounds.width;
        position.height = bounds.height;
      }
      position.maximized = mainWindow.isMaximized();
    }
  }

  mainWindow.on( 'close', () => {
    mainWindow.removeListener( 'resize', handleStateChange );
    mainWindow.removeListener( 'move', handleStateChange );

    config.position = position;
  } );

  mainWindow.on( 'closed', () => {
    mainWindow = null;
  } );

  if ( process.platform == 'darwin' )
    makeDarwinMenu();
}
