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

const rootPath = path.resolve( __dirname, '../..' );

const out = path.resolve( __dirname, '../../packages' );
const dirPath = path.join( out, 'webissues' );
const zipPath = path.join( out, 'WebIssues-Server-v' + version + '.zip' );

if ( fs.existsSync( dirPath ) )
  rimraf.sync( dirPath );

mkdirp.sync( dirPath );

[ 'LICENSE', 'README.md', '.htaccess', 'index.php' ].forEach( name => fs.copyFileSync( path.join( rootPath, name ), path.join( dirPath, name ) ) );

[ 'assets', 'client', 'common', 'cron', 'server', 'setup', 'system', 'users' ].forEach( name => {
  glob.sync( name + '/**', { cwd: rootPath, nodir: true, dot: true, ignore: '**/*.ts' } ).forEach( match => {
    const srcPath = path.join( rootPath, match );
    const destPath = path.join( dirPath, match );
    const destDir = path.dirname( destPath );
    if ( !fs.existsSync( destDir ) )
      mkdirp.sync( destDir );
    fs.copyFileSync( srcPath, destPath );
  } );
} );

mkdirp.sync( path.join( dirPath, 'data' ) );
fs.copyFileSync( path.join( rootPath, 'data/.htaccess' ), path.join( dirPath, 'data/.htaccess' ) );

const output = fs.createWriteStream( zipPath );
const archive = archiver( 'zip', { zlib: { level: 9 } } );

archive.pipe( output );
archive.glob( 'webissues/**', { cwd: out, dot: true } );
archive.finalize();
