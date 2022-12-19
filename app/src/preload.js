const { contextBridge, ipcRenderer } = require( 'electron' );

const url = require( 'url' );

let progressHandler = null;
let doneHandler = null;

const ipcAPI = {
  onStartClient( callback ) {
    ipcRenderer.on( 'start-client', ( event, initialSettings, sytemLocale ) => {
      callback( initialSettings, sytemLocale );
    } );
  },

  openURL( url ) {
    ipcRenderer.send( 'open-url', url );
  },

  pathToURL( path ) {
    return url.format( { pathname: path, protocol: 'file:', slashes: true } );
  },

  saveSettings( settings ) {
    ipcRenderer.send( 'save-settings', settings );
  },

  restartClient( settings ) {
    ipcRenderer.send( 'restart-client', settings );
  },

  findAttachment( serverUUID, fileId ) {
    return new Promise( ( resolve, reject ) => {
      ipcRenderer.once( 'find-attachment-result', ( event, errorMessage, filePath ) => {
        if ( errorMessage != null )
          return reject( new Error( errorMessage ) );
        resolve( filePath );
      } );

      ipcRenderer.send( 'find-attachment', serverUUID, fileId );
    } );
  },

  downloadAttachment( serverUUID, fileId, name, size, url, progressCallback, doneCallback ) {
    progressHandler = ( event, received ) => {
      progressCallback( received );
    }

    doneHandler = ( event, errorMessage, filePath ) => {
      if ( errorMessage != null )
        doneCallback( new Error( errorMessage ), null );
      else
        doneCallback( null, filePath );

      ipcRenderer.removeListener( 'download-attachment-progress', progressHandler );

      doneHandler = null;
      progressHandler = null;
    };

    ipcRenderer.on( 'download-attachment-progress', progressHandler );
    ipcRenderer.once( 'download-attachment-result', doneHandler );

    ipcRenderer.send( 'download-attachment', serverUUID, fileId, name, size, url );
  },

  abortAttachment() {
    if ( progressHandler != null )
      ipcRenderer.removeListener( 'download-attachment-progress', progressHandler );
    if ( doneHandler != null )
      ipcRenderer.removeListener( 'download-attachment-result', doneHandler );

    progressHandler = null;
    doneHandler = null;

    ipcRenderer.send( 'abort-attachment' );
  },

  saveAttachment( filePath, name ) {
    return new Promise( ( resolve, reject ) => {
      ipcRenderer.once( 'save-attachment-result', ( event, errorMessage, targetPath ) => {
        if ( errorMessage != null )
          reject( new Error( errorMessage ) );
        else
          resolve( targetPath );
      } );

      ipcRenderer.send( 'save-attachment', filePath, name );
    } );
  },

  loadIssue( serverUUID, issueId ) {
    return new Promise( ( resolve, reject ) => {
      ipcRenderer.once( 'load-issue-result', ( event, errorMessage, data ) => {
        if ( errorMessage != null )
          return reject( new Error( errorMessage ) );
        resolve( data );
      } );

      ipcRenderer.send( 'load-issue', serverUUID, issueId );
    } );
  },

  saveIssue( serverUUID, issueId, data ) {
    return new Promise( ( resolve, reject ) => {
      ipcRenderer.once( 'save-issue-result', ( event, errorMessage ) => {
        if ( errorMessage != null )
          return reject( new Error( errorMessage ) );
        resolve();
      } );

      ipcRenderer.send( 'save-issue', serverUUID, issueId, data );
    } );
  }
};

contextBridge.exposeInMainWorld( '__WI_API', ipcAPI );
