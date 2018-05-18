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

const packager = require( 'electron-packager' );

const Archiver = require( 'archiver/lib/core' );
const zip = require( 'archiver/lib/plugins/zip' );

const platform = process.argv[ 2 ] || process.platform;
const arch = process.argv[ 3 ] || process.arch;

const out = path.resolve( __dirname, '../../../packages' );

if ( platform == 'darwin' && process.platform == 'win32' ) {
  const _updateQueueTaskWithStats = Archiver.prototype._updateQueueTaskWithStats;

  const executables = [
    'crashpad_handler',
    'Electron Framework',
    'libffmpeg.dylib',
    'libnode.dylib',
    'Mantle',
    'ReactiveCocoa',
    'ShipIt',
    'Squirrel',
    'WebIssues',
    'WebIssues Helper',
    'WebIssues Helper EH',
    'WebIssues Helper NP'
  ];

  Archiver.prototype._updateQueueTaskWithStats = function( task, stats ) {
    task = _updateQueueTaskWithStats.call( this, task, stats );
    if ( task.data.type == 'symlink' ) {
      task.data.linkname = task.data.linkname.replace( /\\/g, '/' );
      task.data.mode = 0755;
    } else if ( task.data.type == 'file' ) {
      if ( executables.includes( path.basename( task.data.name ) ) )
        task.data.mode = 0755;
      else
        task.data.mode = 0644;
    }
    return task;
  }
}

function createZipArchive( options ) {
  const archive = new Archiver( 'zip', options );
  archive.setFormat( 'zip' );
  archive.setModule( new zip( options ) );
  return archive;
}

packager( {
  name: 'WebIssues',
  dir: path.resolve( __dirname, '../..' ),
  ignore: [ /\/index-dev.html$/, /\/src$/ ],
  out,
  overwrite: true,
  platform,
  arch,
  icon: path.resolve( __dirname, '../icons/webissues' + ( platform == 'darwin' ? '.icns' : '.ico' ) ),
  appCopyright: 'Copyright (C) 2007-2017 WebIssues Team',

  afterExtract: [
    ( buildPath, electronVersion, platform, arch, callback ) => {
      const licenseSrc = path.resolve( __dirname, '../../../LICENSE' );
      const licenseDest = path.join( buildPath, 'LICENSE' );

      fs.rename( licenseDest, licenseDest + '.electron', err => {
        if ( err != null )
          console.error( err );

        fs.copyFile( licenseSrc, licenseDest, err => {
          if ( err != null )
            console.error( err );

          callback();
        } );
      } );
    }
  ],
} ).then( () => {
  const dirname = 'WebIssues-' + platform + '-' + arch;
  const zipname = 'webissues-client-' + platform + '-' + arch + '.zip';

  const output = fs.createWriteStream( path.join( out, zipname ) );
  const archive = createZipArchive( { zlib: { level: 9 } } );

  archive.pipe( output );
  archive.directory( path.join( out, dirname ), false );
  archive.finalize();
} );
