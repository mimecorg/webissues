<!--
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
-->

<template>
  <BaseForm v-bind:title="name" v-bind:size="size">
    <Prompt v-if="imageError" alert-class="alert-warning" path="error.ImagePreviewError"/>
    <div ref="image" class="issue-image"></div>
    <div v-if="!imageLoaded" class="progress">
      <div v-bind:class="[ 'progress-bar', error != null ? 'progress-bar-danger' : 'progress-bar-success' ]" v-bind:style="{ width: receivedPercent }"></div>
    </div>
    <div v-if="!imageLoaded" v-bind:class="[ 'progress-message', { 'has-error': error != null } ]">
      <p v-if="error != null" class="help-block">{{ error }}</p>
      <p v-else class="help-block">{{ $t( 'info.Downloaded', [ receivedPercent, formattedFileSize ] ) }}</p>
    </div>
    <div class="form-buttons">
      <button v-if="path != null" class="btn btn-primary" v-on:click="open">{{ $t( 'cmd.Open' ) }}</button>
      <button v-if="path != null" class="btn btn-primary" v-on:click="saveAs">{{ $t( 'cmd.Save' ) }}</button>
      <button class="btn btn-default" v-on:click="returnToDetails">{{ $t( 'cmd.Cancel' ) }}</button>
    </div>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    issueId: Number,
    fileId: Number,
    name: String,
    fileSize: Number,
    initialPath: String
  },

  data() {
    return {
      path: this.initialPath,
      received: this.initialPath != null ? this.fileSize : 0,
      error: null,
      imageLoaded: false,
      imageError: false,
      width: 0
    };
  },

  computed: {
    ...mapState( 'global', [ 'baseURL', 'serverUUID' ] ),
    size() {
      if ( this.width <= 676 )
        return 'normal';
      else
        return 'large';
    },
    receivedPercent() {
      if ( this.error != null || this.fileSize == 0 )
        return '100%';
      else
        return '' + Math.floor( 100 * this.received / this.fileSize ) + '%';
    },
    formattedFileSize() {
      return this.$formatter.formatFileSize( this.fileSize );
    }
  },

  methods: {
    open() {
      this.$client.openFile( this.path );
      this.returnToDetails();
    },

    saveAs() {
      this.$form.block();
      this.$client.saveAttachment( this.path, this.name ).then( targetPath => {
        if ( targetPath != null )
          this.returnToDetails();
        else
          this.$form.unblock();
      } ).catch( error => {
        console.error( error );
        this.$form.unblock();
        this.error = this.$t( 'error.SaveError' );
      } );
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    },

    progress( received ) {
      this.received = received;
    },

    done( error, filePath ) {
      if ( error != null ) {
        console.error( error );
        this.error = this.$t( 'error.DownloadError' );
      } else {
        this.path = filePath;
        this.received = this.fileSize;
        this.previewImage();
      }
    },

    previewImage() {
      if ( /\.(png|jpe?g|gif|bmp|ico|svg)$/i.test( this.name ) ) {
        const image = document.createElement( 'img' );
        image.onload = () => {
          this.imageLoaded = true;
          this.width = image.width;
          this.$refs.image.appendChild( image );
        };
        image.onerror = () => {
          this.imageLoaded = true;
          this.imageError = true;
        };
        image.src = this.$client.pathToURL( this.path );
      }
    }
  },

  mounted() {
    if ( this.path == null ) {
      const url = this.baseURL + '/server/api/issues/files/download.php?id=' + this.fileId;
      this.$client.downloadAttachment( this.serverUUID, this.fileId, this.name, this.fileSize, url, this.progress, this.done );
    } else {
      this.previewImage();
    }
  },

  beforeDestroy() {
    if ( this.path == null )
      this.$client.abortAttachment();
  }
}
</script>
