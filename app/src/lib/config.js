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

const electron = require( 'electron' );

const path = require( 'path' );

const { dataPath, loadJSON, saveJSON } = require( './files' );

const config = {
  settings: {
    baseURL: null
  },
  position: {
    x: null,
    y: null,
    width: 1280,
    height: 800,
    maximized: true
  }
};

function loadConfiguraton( callback ) {
  loadJSON( path.join( dataPath, 'config.json' ), ( error, data ) => {
    if ( error == null && data != null )
      mergeConfiguration( config, data );

    callback( error, config );
  } );
}

function saveConfiguration( callback ) {
  saveJSON( path.join( dataPath, 'config.json' ), config, callback );
}

function mergeConfiguration( target, source ) {
  for ( const key in source ) {
    if ( target[ key ] != null && typeof target[ key ] == 'object' && source[ key ] != null && typeof source[ key ] == 'object' )
      mergeConfiguration( target[ key ], source[ key ] );
    else
      target[ key ] = source[ key ];
  }
}

function adjustPosition( position ) {
  let workArea;
  if ( position.x != null && position.y != null )
    workArea = electron.screen.getDisplayMatching( position ).workArea;
  else
    workArea = electron.screen.getPrimaryDisplay().workArea;

  if ( position.width > workArea.width )
    position.width = workArea.width;
  if ( position.height > workArea.height )
    position.height = workArea.height;

  if ( position.x != null && position.y != null ) {
    if ( position.x >= workArea.x + workArea.width )
      position.x = workArea.x + workArea.width - position.width;
    if ( position.y >= workArea.y + workArea.height )
      position.y = workArea.y + workArea.height - position.height;
  }
}

module.exports = {
  config,
  loadConfiguraton,
  saveConfiguration,
  adjustPosition
};
