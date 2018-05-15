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

const archiver = require( 'archiver' );
const fs = require( 'fs' );
const packager = require( 'electron-packager' );
const path = require( 'path' );

const platform = process.argv[ 2 ];
const arch = process.argv[ 3 ];

const out = path.resolve( __dirname, '../../../packages' );

packager( {
  name: 'WebIssues',
  dir: path.resolve( __dirname, '../..' ),
  ignore: [ /\/index-dev.html$/, /\/src$/ ],
  out,
  overwrite: true,
  platform,
  arch,
  icon: path.resolve( __dirname, '../icons/webissues' + ( platform == 'darwin' ? '.icns' : '.ico' ) ),
  appCopyright: 'Copyright (C) 2007-2017 WebIssues Team'
} ).then( () => {
  const dirname = 'WebIssues-' + platform + '-' + arch;
  const zipname = 'webissues-client-' + platform + '-' + arch + '.zip';

  const output = fs.createWriteStream( path.join( out, zipname ) );
  const archive = archiver( 'zip', { zlib: { level: 9 } } );

  archive.pipe( output );
  archive.directory( path.join( out, dirname ), false );
  archive.finalize();
} );
