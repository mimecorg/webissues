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

const { app, BrowserWindow, ipcMain, Menu, shell, nativeImage } = require( 'electron' );

const path = require( 'path' );
const url = require( 'url' );

const { config, loadConfiguraton, saveConfiguration, adjustPosition } = require( './lib/config' );
const { initializeAttachments } = require( './lib/attachments' );
const { initializeIssues } = require( './lib/issues' );
const { makeContextMenuHandler, makeDarwinMenu } = require( './lib/menus' );

let configSaved = false;

let mainWindow = null;

app.allowRendererProcessReuse = true;

app.on( 'ready', () => {
  loadConfiguraton( () => {
    initializeAttachments( () => {
      initializeIssues( () => {
        createWindow();
      } );
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

ipcMain.on( 'save-settings', ( event, settings ) => {
  config.settings = settings;
  saveConfiguration( () => {} );
} );

ipcMain.on( 'restart-client', ( event, settings ) => {
  config.settings = settings;
  saveConfiguration( () => {
    event.sender.session.clearStorageData( { storages: [ 'cookies' ] } ).then( () => {
      event.sender.send( 'start-client', config.settings, app.getLocale() );
    } );
  } );
} );

ipcMain.on( 'open-url', ( event, externalUrl ) => {
  shell.openExternal( externalUrl );
} );

function createWindow() {
  let preloadPath;

  if ( process.env.NODE_ENV == 'production' )
    preloadPath = path.join( __dirname, 'preload.min.js' );
  else
    preloadPath = path.join( __dirname, 'preload.js' );

  if ( process.platform == 'darwin' )
    makeDarwinMenu();
  else if ( process.env.NODE_ENV == 'production' )
    Menu.setApplicationMenu( null );

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
    title: 'WebIssues',
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: preloadPath
    }
  } );

  if ( process.platform == 'linux' ) {
    if ( process.env.NODE_ENV == 'production' )
      mainWindow.setIcon( nativeImage.createFromPath( path.join( __dirname, '../images/webissues-logo.png' ) ) );
    else
      mainWindow.setIcon( nativeImage.createFromPath( path.join( __dirname, '../../src/images/webissues-logo.png' ) ) );
  }

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
    mainWindow.webContents.send( 'start-client', config.settings, app.getLocale() );
  } );

  mainWindow.webContents.on( 'will-navigate', ( event, url ) => {
    if ( url != mainWindow.webContents.getURL() ) {
      shell.openExternal( url );
      event.preventDefault();
    }
  } );

  mainWindow.webContents.setWindowOpenHandler( ( { url } ) => {
    if ( url != mainWindow.webContents.getURL() )
      shell.openExternal( url );
    return { action: 'deny' };
  } );

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
}
