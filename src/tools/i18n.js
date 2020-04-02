const path = require( 'path' );
const fs = require( 'fs' );

const glob = require( 'glob' );

if ( process.argv.length < 3 || process.argv.length > 4 ) {
  console.error( 'Usage: node src/tools/i18n.js INPUT_DIR [LANG_CODE]' );
  return;
}

if ( process.argv.length == 4 )
  importTranslations( path.join( process.argv[ 2 ], process.argv[ 3 ] ), process.argv[ 3 ].replace( '-', '_' ) );
else
  updateTranslations( process.argv[ 2 ] );

function updateTranslations( inputDir ) {
  const all = glob.sync( '*/', { cwd: inputDir } ).map( name => name.replace( '/', '' ) );
  const existing = glob.sync( '*.json', { cwd: path.resolve( __dirname, '../i18n' ), nodir: true } ).map( name => path.basename( name, '.json' ) );

  for ( const langCode of all ) {
    const parts = langCode.split( '-' );
    if ( parts.length > 1 && existing.includes( parts.join( '_' ) ) )
      importTranslations( path.join( inputDir, langCode ), parts.join( '_' ) );
    else if ( existing.includes( parts[ 0 ] ) )
      importTranslations( path.join( inputDir, langCode ), parts[ 0 ] );
    else
      console.log( 'skipping ' + langCode );
  }
}

function importTranslations( inputDir, langCode ) {
  console.log( 'importing ' + langCode + ' from ' + inputDir );

  processFile( path.join( inputDir, 'common.json' ), path.resolve( __dirname, '../../common/i18n/' + langCode + '.json' ) );
  processFile( path.join( inputDir, 'src.json' ), path.resolve( __dirname, '../i18n/' + langCode + '.json' ) );
}

function processFile( inputPath, outputPath ) {
  const data = JSON.parse( fs.readFileSync( inputPath, 'utf8' ) );

  const stats = { total: 0, translated: 0 };
  removeEmpty( data, stats );

  console.log( 'translated ' + stats.translated + ' of ' + stats.total + ' strings' );

  fs.writeFileSync( outputPath, JSON.stringify( data, null, 2 ) + '\n' );
}

function removeEmpty( data, stats ) {
  let empty = true;
  for ( const key in data ) {
    const value = data[ key ];
    if ( typeof value == 'object' ) {
      if ( removeEmpty( value, stats ) )
        delete data[ key ];
      else
        empty = false;
    } else {
      stats.total++;
      if ( value === '' ) {
        delete data[ key ];
      } else {
        stats.translated++;
        empty = false;
      }
    }
  }
  return empty;
}
