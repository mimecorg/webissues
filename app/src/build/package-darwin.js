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

const fs = require( 'fs' );
const path = require( 'path' );

const archiver = require( 'archiver' );
const installer = require( 'electron-installer-dmg' );

async function buildPlatformPackage( { out, dirName, version } ) {
  await buildArchive( out, dirName );
  await buildInstaller( out, dirName, version );
}

async function buildArchive( out, dirName ) {
  console.log( 'Building zip archive' );

  const output = fs.createWriteStream( path.join( out, dirName + '.zip' ) );
  const archive = archiver( 'zip', { zlib: { level: 9 } } );

  archive.pipe( output );
  archive.glob( dirName + '/**', { cwd: out } );
  await archive.finalize();
}

async function buildInstaller( out, dirName, version ) {
  console.log( 'Building dmg package' );

  await installer( {
    appPath: path.join( out, dirName, 'WebIssues.app' ),
    name: dirName,
    title: 'WebIssues ' + version,
    background: path.resolve( __dirname, '../images/dmg-background.png' ),
    icon: path.resolve( __dirname, '../icons/webissues.icns' ),
    overwrite: true,
    out
  } );
}

module.exports = buildPlatformPackage;
