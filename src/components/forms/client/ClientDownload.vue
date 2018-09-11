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
  <div class="container-fluid">
    <FormHeader v-bind:title="$t( 'title.DownloadFile' )" v-on:close="close"/>
    <Prompt path="prompt.DownloadFile"><strong>{{ name }}</strong></Prompt>
    <div class="progress">
      <div v-bind:class="[ 'progress-bar', error != null ? 'progress-bar-danger' : 'progress-bar-success' ]" v-bind:style="{ width: receivedPercent }"></div>
    </div>
    <div v-bind:class="[ 'progress-message', { 'has-error': error != null } ]">
      <p v-if="error != null" class="help-block">{{ error }}</p>
      <p v-else class="help-block">{{ $t( 'info.Downloaded', [ receivedPercent, fileSize ] ) }}</p>
    </div>
    <div class="form-buttons">
      <button v-if="path != null" class="btn btn-primary" v-on:click="open">{{ $t( 'cmd.Open' ) }}</button>
      <button v-if="path != null" class="btn btn-primary" v-on:click="saveAs">{{ $t( 'cmd.SaveAs' ) }}</button>
      <button class="btn btn-default" v-on:click="cancel">{{ $t( 'cmd.Cancel' ) }}</button>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    issueId: Number,
    fileId: Number,
    name: String,
    total: Number,
    fileSize: String,
    initialPath: String
  },

  data() {
    return {
      path: this.initialPath,
      received: 0,
      error: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'baseURL', 'serverUUID' ] ),
    receivedPercent() {
      if ( this.error != null || this.total == 0 )
        return '100%';
      else
        return '' + Math.floor( 100 * this.received / this.total ) + '%';
    }
  },

  methods: {
    open() {
      this.$client.openFile( this.path );
      this.returnToDetails();
    },

    saveAs() {
      this.$emit( 'block' );
      this.$client.saveAttachment( this.path, this.name ).then( targetPath => {
        if ( targetPath != null )
          this.returnToDetails();
        else
        this.$emit( 'unblock' );
      } ).catch( error => {
        console.error( error );
        this.$emit( 'unblock' );
        this.error = this.$t( 'error.SaveError' );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    },

    close() {
      this.$emit( 'close' );
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
        this.received = this.total;
      }
    }
  },

  mounted() {
    if ( this.path == null ) {
      const url = this.baseURL + '/server/api/issues/files/download.php?id=' + this.fileId;
      this.$client.downloadAttachment( this.serverUUID, this.fileId, this.name, this.total, url, this.progress, this.done );
    } else {
      this.received = this.total;
    }
  },

  beforeDestroy() {
    if ( this.path == null )
      this.$client.abortAttachment();
  }
}
</script>
