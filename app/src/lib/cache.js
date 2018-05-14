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

const fs = require( 'fs' );
const path = require( 'path' );

const { dataPath, loadJSON, saveJSON } = require( './files' );

function makeCache( dirName, { maxSize, maxCount } ) {
  let records = [];

  return {
    initialize,
    save,
    find,
    push,
    getFilePath,
    allocateSpace
  };

  function initialize( callback ) {
    loadJSON( path.join( dataPath, dirName + '.json' ), ( error, data ) => {
      if ( error == null && data != null )
        records = data;

      fs.mkdir( path.join( dataPath, dirName ), error => {
        allocateSpace( 0, callback );
      } );
    } );
  }

  function save( callback ) {
    saveJSON( path.join( dataPath, dirName + '.json' ), records, callback );
  }

  function find( predicate ) {
    return records.find( predicate );
  }

  function push( record ) {
    records.push( record );
  }

  function getFilePath( name ) {
    return path.join( dataPath, dirName, name );
  }

  function allocateSpace( allocated, callback ) {
    const sorted = [ ...records ];
    sorted.sort( ( r1, r2 ) => r1.lastAccess - r2.lastAccess );

    let modified = false;

    checkRecord( 0 );

    function checkRecord( index ) {
      if ( index >= sorted.length )
        return finish();

      let size = records.reduce( ( sum, r ) => sum + r.size, 0 );
      let count = records.length;

      if ( allocated > 0 ) {
        size += allocated;
        count++;
      }

      const filePath = getFilePath( sorted[ index ].name );

      if ( sorted[ index ].lastAccess < 0 || size > maxSize || count > maxCount ) {
        fs.unlink( filePath, error => {
          if ( error != null && error.code == 'ENOENT' || error == null )
            removeRecord( sorted[ index ].name );
          checkRecord( index + 1 );
        } );
      } else {
        fs.access( filePath, error => {
          if ( error != null && error.code == 'ENOENT' )
            removeRecord( sorted[ index ].name );
          checkRecord( index + 1 );
        } );
      }
    }

    function removeRecord( name ) {
      records = records.filter( r => r.name != name );
      modified = true;
    }

    function finish() {
      if ( modified ) {
        save( error => {
          callback();
        } );
      } else {
        callback();
      }
    }
  }
}

module.exports = {
  makeCache
};
