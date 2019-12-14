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

const platform = process.argv[ 2 ] || process.platform;
const arch = process.argv[ 3 ] || process.arch;

const fs = require( 'fs' );
const path = require( 'path' );

const packager = require( 'electron-packager' );
const rimraf = require( 'rimraf' );

const version = require( '../../../package' ).version;

buildPackage().catch( error => {
  console.error( error );
  process.exit( 1 );
} );

async function buildPackage() {
  const out = path.resolve( __dirname, '../../../packages' );

  let icon = null;
  if ( platform == 'win32' )
    icon = path.resolve( __dirname, '../icons/webissues.ico' );
  else if ( platform == 'darwin' )
    icon = path.resolve( __dirname, '../icons/webissues.icns' );

  const buildVersion = makeBuildVersion( version );

  await packager( {
    name: 'WebIssues',
    dir: path.resolve( __dirname, '../..' ),
    ignore: [ /\/index-dev.html$/, /\/src$/ ],
    out,
    overwrite: true,
    platform,
    arch,
    buildVersion,
    executableName: ( platform == 'linux' ) ? 'webissues' : 'WebIssues',
    icon,
    appCopyright: 'Copyright (C) 2007-2017 WebIssues Team',
    appBundleId: 'org.mimec.webissues',
    appCategoryType: 'public.app-category.developer-tools',

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
  } );

  const dirName = 'webissues-' + version + '-' + platform + '-' + arch;
  const dirPath = path.join( out, dirName );

  if ( fs.existsSync( dirPath ) )
    rimraf.sync( dirPath );

  fs.renameSync( path.join( out, 'WebIssues-' + platform + '-' + arch ), dirPath );

  if ( process.platform == platform ) {
    const buildPlatformPackage = require( './package-' + platform );

    await buildPlatformPackage( { out, dirName, version, buildVersion, platform, arch } );
  }
}

function makeBuildVersion( version ) {
  const start = new Date( '2000-01-01' );

  const now = new Date();
  const today = new Date( now.getFullYear(), now.getMonth(), now.getDate() );

  const build = Math.round( ( today - start ) / 86400000 ) + 1;

  const index = version.indexOf( '-' );

  if ( index < 0 )
    return version + '.' + build;
  else
    return version.substr( 0, index ) + '.' + build;
}
