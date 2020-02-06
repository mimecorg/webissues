<!--
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
-->

<template>
  <BaseForm v-bind:title="name" v-bind:size="size">
    <Prompt v-if="error" alert-class="alert-warning" path="error.ImagePreviewError"/>
    <div ref="image" class="issue-image"></div>
    <div v-if="!loaded" class="busy-container">
      <div class="busy-spinner">
        <span class="fa fa-spinner fa-spin" aria-hidden="true"></span>
      </div>
    </div>
    <div class="form-buttons">
      <button v-if="loaded" class="btn btn-primary" v-on:click="open">{{ $t( 'cmd.Open' ) }}</button>
      <button v-if="loaded" class="btn btn-primary" v-on:click="saveAs">{{ $t( 'cmd.Save' ) }}</button>
      <button class="btn btn-default" v-on:click="returnToDetails">{{ $t( 'cmd.Cancel' ) }}</button>
    </div>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

import FileSaver from 'file-saver'

export default {
  props: {
    issueId: Number,
    fileId: Number,
    name: String
  },
  data() {
    return {
      loaded: false,
      error: false,
      width: 0
    };
  },
  computed: {
    ...mapState( 'global', [ 'baseURL' ] ),
    size() {
      if ( this.width <= 676 )
        return 'normal';
      else
        return 'large';
    },
    fileURL() {
      return this.baseURL + '/client/file.php?id=' + this.fileId;
    }
  },
  methods: {
    open() {
      const link = document.createElement( 'a' );
      link.href = this.fileURL;
      link.target = '_blank';
      link.click();
      this.returnToDetails();
    },
    saveAs() {
      FileSaver.saveAs( this.fileURL, this.name );
      this.returnToDetails();
    },
    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    }
  },
  mounted() {
    const image = document.createElement( 'img' );
    image.onload = () => {
      this.loaded = true;
      this.width = image.width;
      this.$refs.image.appendChild( image );
    };
    image.onerror = () => {
      this.loaded = true;
      this.error = true;
    };
    image.src = this.fileURL;
  }
}
</script>
