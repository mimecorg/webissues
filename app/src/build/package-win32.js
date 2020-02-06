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
const child_process = require( 'child_process' );

const archiver = require( 'archiver' );

async function buildPlatformPackage( { out, dirName, version, buildVersion, arch } ) {
  await buildArchive( out, dirName );

  if ( arch == 'ia32' )
    await buildInstaller( out, dirName, version, buildVersion );
}

async function buildArchive( out, dirName ) {
  console.log( 'Building zip archive' );

  const output = fs.createWriteStream( path.join( out, dirName + '.zip' ) );
  const archive = archiver( 'zip', { zlib: { level: 9 } } );

  archive.pipe( output );
  archive.glob( dirName + '/**', { cwd: out } );
  await archive.finalize();
}

async function buildInstaller( out, dirName, version, buildVersion ) {
  console.log( 'Building installer' );

  const makensisPath = findMakensis();

  const cwd = path.resolve( __dirname, '../nsis' );

  const args = [
    '/DSRCDIR=' + path.join( out, dirName ),
    '/DOUTDIR=' + out,
    '/DOUTFILE=' + dirName + '.exe',
    '/DVERSION=' + version,
    '/DBUILDVERSION=' + buildVersion,
    'installer.nsi'
  ];

  await spawnProcess( makensisPath, args, { cwd } );
}

function findMakensis() {
  const programFiles32 = process.env[ 'ProgramFiles(x86)' ];

  const makensisPath = path.join( programFiles32, 'NSIS/bin/makensis.exe' );

  if ( !fs.existsSync( makensisPath ) )
    throw new Error( 'Could not find makensis.exe, make sure that you have NSIS installed' );

  console.log( 'makensis.exe found: ' + makensisPath );

  return makensisPath;
}

function spawnProcess( command, args, options = {} ) {
  return new Promise( ( resolve, reject ) => {
    const process = child_process.spawn( command, args, { ...options, stdio: 'inherit' } );
    process.on( 'exit', () => resolve() );
    process.on( 'error', error => reject( error ) );
  } );
}

module.exports = buildPlatformPackage;
