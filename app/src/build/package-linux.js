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
const debianInstaller = require( 'electron-installer-debian' );
const redhatInstaller = require( 'electron-installer-redhat' );

const package = require( '../../../package' );

async function buildPlatformPackage( { out, dirName, version, arch } ) {
  await buildArchive( out, dirName );
  await buildDebianInstaller( out, dirName, version, arch );
  await buildRedhatInstaller( out, dirName, version, arch );
}

async function buildArchive( out, dirName ) {
  console.log( 'Building tar.gz archive' );

  const output = fs.createWriteStream( path.join( out, dirName + '.tar.gz' ) );
  const archive = archiver( 'tar', { gzip: true, gzipOptions: { level: 9 } } );

  archive.pipe( output );
  archive.glob( dirName + '/**', { cwd: out } );
  await archive.finalize();
}

async function buildDebianInstaller( out, dirName, version, arch ) {
  console.log( 'Building deb package' );

  const debianArch = ( arch == 'x64' ) ? 'amd64' : 'i386';

  await debianInstaller( {
    src: path.join( out, dirName ),
    dest: out,
    rename: ( dest, src ) => path.join( dest, `webissues-${version}-linux-${debianArch}.deb` ),
    name: 'webissues',
    productName: 'WebIssues',
    genericName: 'Issue Tracker',
    description: package.description,
    productDescription: package.description,
    version,
    section: 'devel',
    arch: debianArch,
    maintainer: 'Michał Męciński <mimec@mimec.org>',
    homepage: 'https://webissues.mimec.org',
    bin: 'webissues',
    icon: {
      '48x48': path.resolve( __dirname, '../icons/webissues-48.png' ),
      '256x256': path.resolve( __dirname, '../icons/webissues-256.png' )
    },
    categories: [ 'Development', 'ProjectManagement' ]
  } );
}

async function buildRedhatInstaller( out, dirName, version, arch ) {
  console.log( 'Building rpm package' );

  const redhatArch = ( arch == 'x64' ) ? 'x86_64' : 'i686';

  await redhatInstaller( {
    src: path.join( out, dirName ),
    dest: out,
    rename: ( dest, src ) => path.join( dest, `webissues-${version}-linux-${redhatArch}.rpm` ),
    name: 'webissues',
    productName: 'WebIssues',
    genericName: 'Issue Tracker',
    description: package.description,
    productDescription: package.description,
    version,
    license: 'AGPLv3+',
    arch: redhatArch,
    homepage: 'https://webissues.mimec.org',
    compressionLevel: 9,
    bin: 'webissues',
    icon: {
      '48x48': path.resolve( __dirname, '../icons/webissues-48.png' ),
      '256x256': path.resolve( __dirname, '../icons/webissues-256.png' )
    },
    categories: [ 'Development', 'ProjectManagement' ]
  } );
}

module.exports = buildPlatformPackage;
