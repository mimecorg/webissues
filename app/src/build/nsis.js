const fs = require( 'fs' );
const path = require( 'path' );
const child_process = require( 'child_process' );

const makeBuildVersion = require( './build-version' );
const version = require( '../../../package' ).version;

const cwd = path.resolve( __dirname, '../nsis' );

const makensisPath = ( process.platform == 'win32' ) ? findMakensis() : 'makensis';

const buildVersion = makeBuildVersion( version );

const args = [
  '/DVERSION=' + version,
  '/DARCHITECTURE=win32-ia32',
  '/DBUILDVERSION=' + buildVersion,
  'installer.nsi'
];

const result = child_process.spawnSync( makensisPath, args, { cwd, stdio: 'inherit' } );

if ( result.error != null )
  throw result.error;

function findMakensis() {
  const programFiles32 = process.env[ 'ProgramFiles(x86)' ];

  const makensisPath = path.join( programFiles32, 'NSIS/bin/makensis.exe' );

  if ( fs.existsSync( makensisPath ) ) {
    console.log( 'makensis.exe found: ' + makensisPath );
    return makensisPath;
  } else {
    console.error( 'Error: Could not find makensis.exe, make sure that you have NSIS installed' );
    process.exit( 1 );
  }
}
