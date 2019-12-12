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

module.exports = makeBuildVersion;
