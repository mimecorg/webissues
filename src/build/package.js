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

const archiver = require( 'archiver' );
const glob = require( 'glob' );
const mkdirp = require( 'mkdirp' );
const rimraf = require( 'rimraf' );

const version = require( '../../package' ).version;

buildPackage().catch( error => {
  console.error( error );
  process.exit( 1 );
} );

async function buildPackage() {
  const rootPath = path.resolve( __dirname, '../..' );

  const out = path.resolve( __dirname, '../../packages' );
  const dirName = 'webissues-server-' + version;
  const dirPath = path.join( out, dirName );

  if ( fs.existsSync( dirPath ) )
    rimraf.sync( dirPath );

  mkdirp.sync( dirPath );

  [ 'LICENSE', 'README.md', '.htaccess', 'index.php', 'web.config' ].forEach( name => fs.copyFileSync( path.join( rootPath, name ), path.join( dirPath, name ) ) );

  [ 'assets', 'client', 'common', 'cron', 'server', 'setup', 'system', 'users' ].forEach( name => {
    glob.sync( name + '/**', { cwd: rootPath, nodir: true, dot: true } ).forEach( match => {
      const srcPath = path.join( rootPath, match );
      const destPath = path.join( dirPath, match );
      const destDir = path.dirname( destPath );
      if ( !fs.existsSync( destDir ) )
        mkdirp.sync( destDir );
      fs.copyFileSync( srcPath, destPath );
    } );
  } );

  await buildArchive( out, dirName, dirName + '.zip', 'zip', { zlib: { level: 9 } } );

  if ( process.platform == 'linux' || process.platform == 'darwin' )
    await buildArchive( out, dirName, dirName + '.tar.gz', 'tar', { gzip: true, gzipOptions: { level: 9 } } );
}

async function buildArchive( out, dirName, fileName, format, options ) {
  const output = fs.createWriteStream( path.join( out, fileName ) );
  const archive = archiver( format, options );

  archive.pipe( output );
  archive.glob( dirName + '/**', { cwd: out, dot: true } );
  await archive.finalize();
}
