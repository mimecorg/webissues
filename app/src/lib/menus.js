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

const { clipboard, Menu, shell } = require( 'electron' );

function makeContextMenuHandler( window ) {
  const inputMenu = Menu.buildFromTemplate( [
    { role: 'undo' },
    { role: 'redo' },
    { type: 'separator' },
    { role: 'cut' },
    { role: 'copy' },
    { role: 'paste' },
    { type: 'separator' },
    { role: 'selectall' },
  ] );

  const selectionMenu = Menu.buildFromTemplate( [
    { role: 'copy' },
  ] );

  const linkMenu = Menu.buildFromTemplate( [
    { label: 'Open link', click: openLink },
    { label: 'Copy link address ', click: copyLink }
  ] );

  let linkURL = null;

  return contextMenuHandler;

  function contextMenuHandler( event, props ) {
    if ( props.isEditable ) {
      inputMenu.items[ 0 ].enabled = props.editFlags.canUndo;
      inputMenu.items[ 1 ].enabled = props.editFlags.canRedo;
      inputMenu.items[ 3 ].enabled = props.editFlags.canCut;
      inputMenu.items[ 4 ].enabled = props.editFlags.canCopy;
      inputMenu.items[ 5 ].enabled = props.editFlags.canPaste;
      inputMenu.items[ 7 ].enabled = props.editFlags.canSelectAll;
      inputMenu.popup( window ) ;
    } else if ( props.selectionText != '' ) {
      selectionMenu.popup( window );
    } else if ( props.linkURL != '' ) {
      let baseURL = props.pageURL;
      const index = baseURL.indexOf( '#' );
      if ( index >= 0 )
        baseURL = baseURL.substr( 0, index );
      if ( !props.linkURL.startsWith( baseURL ) ) {
        linkURL = props.linkURL;
        linkMenu.popup( window );
      }
    }
  }

  function openLink() {
    shell.openExternal( linkURL );
  }

  function copyLink() {
    clipboard.writeText( linkURL );
  }
}

function makeDarwinMenu() {
  const template = [
    {
      label: 'WebIssues',
      submenu: [
        { role: 'about' },
        { type: 'separator' },
        { role: 'services', submenu: [] },
        { type: 'separator' },
        { role: 'hide' },
        { role: 'hideothers' },
        { role: 'unhide' },
        { type: 'separator' },
        { role: 'quit' }
      ]
    },
    {
      label: 'Edit',
      submenu: [
        { role: 'undo' },
        { role: 'redo' },
        { type: 'separator' },
        { role: 'cut' },
        { role: 'copy' },
        { role: 'paste' },
        { role: 'pasteandmatchstyle' },
        { role: 'delete' },
        { role: 'selectall' },
        { type: 'separator' },
        {
          label: 'Speech',
          submenu: [
            { role: 'startspeaking' },
            { role: 'stopspeaking' }
          ]
        }
      ]
    },
    {
      label: 'View',
      submenu: [
        { role: 'resetzoom' },
        { role: 'zoomin' },
        { role: 'zoomout' },
        { type: 'separator' },
        { role: 'togglefullscreen' }
      ]
    },
    {
      role: 'window',
      submenu: [
        { role: 'close' },
        { role: 'minimize' },
        { role: 'zoom' },
        { type: 'separator' },
        { role: 'front' }
      ]
    },
    {
      role: 'help',
      submenu: []
    }
  ];

  const menu = Menu.buildFromTemplate( template );
  Menu.setApplicationMenu( menu );
}

module.exports = {
  makeContextMenuHandler,
  makeDarwinMenu
};
