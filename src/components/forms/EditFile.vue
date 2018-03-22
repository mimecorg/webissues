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
    <FormHeader v-bind:title="title" v-on:close="close"/>
    <Prompt v-if="mode == 'edit'" path="EditFile.EditFilePrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditFile.AddFilePrompt"><strong>{{ issueName }}</strong></Prompt>
    <FormGroup v-if="mode == 'add'" v-bind:error="fileError">
      <div v-bind:class="[ 'form-upload', { 'drag-over': dragOver } ]">
        <input ref="file" id="file" type="file" class="form-control" v-on:change="fileChange">
        <p>{{ filePrompt }}</p>
      </div>
    </FormGroup>
    <FormGroup id="name" v-bind:label="$t( 'EditFile.FileName' )" v-bind:required="true" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="nameMaxLength" v-model="nameValue">
    </FormGroup>
    <FormGroup id="description" v-bind:label="$t( 'EditFile.Description' )" v-bind:error="descriptionError">
      <input ref="description" id="description" type="text" class="form-control" v-bind:maxlength="descriptionMaxLength" v-model="descriptionValue">
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength } from '@/constants'

export default {
  props: {
    mode: String,
    issueId: Number,
    issueName: String,
    fileId: Number,
    name: String,
    description: String
  },

  data() {
    return {
      filePrompt: this.$t( 'EditFile.FilePrompt' ),
      fileError: null,
      nameValue: this.name,
      nameMaxLength: MaxLength.FileName,
      nameError: null,
      descriptionValue: this.description,
      descriptionMaxLength: MaxLength.FileDescription,
      descriptionError: null,
      dragOver: false
    };
  },

  computed: {
    ...mapState( 'global', [ 'settings' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditFile.EditFile' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditFile.AddFile' );
    }
  },

  methods: {
    submit() {
      this.fileError = null;
      this.nameError = null;
      this.descriptionError = null;

      const data = {};
      if ( this.mode == 'add' )
        data.issueId = this.issueId;
      else
        data.fileId = this.fileId;
      let file = null;
      let modified = false;
      let valid = true;

      if ( this.mode == 'add' ) {
        if ( this.$refs.file.files.length == 0 ) {
          this.fileError = this.$t( 'EditFile.NoFileSelected' );
          valid = false;
        } else {
          file = this.$refs.file.files[ 0 ];
          if ( file.size > this.settings.fileMaxSize ) {
            this.fileError = this.$t( 'EditFile.FileTooLarge' );
            valid = false;
          }
        }
      }

      try {
        this.nameValue = this.$parser.normalizeString( this.nameValue, MaxLength.FileName, { allowEmpty: false } );
        if ( this.mode == 'add' || this.nameValue != this.name )
          modified = true;
        data.name = this.nameValue;
      } catch ( error ) {
        if ( error.reason == 'APIError' ) {
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          if ( valid )
            this.$refs.name.focus();
          valid = false;
        } else {
          throw error;
        }
      }

      if ( this.nameError == null && ( this.nameValue.charAt( 0 ) == '.' || /[\\/:*?"<>|]/.test( this.nameValue ) ) ) {
        this.nameError = this.$t( 'EditFile.InvalidFileName' );
        if ( valid )
          this.$refs.name.focus();
        valid = false;
      }

      try {
        this.descriptionValue = this.$parser.normalizeString( this.descriptionValue, MaxLength.FileDescription, { allowEmpty: true } );
        if ( this.mode == 'add' || this.descriptionValue != this.description )
          modified = true;
        data.description = this.descriptionValue;
      } catch ( error ) {
        if ( error.reason == 'APIError' ) {
          this.descriptionError = this.$t( 'ErrorCode.' + error.errorCode );
          if ( valid )
            this.$refs.description.focus();
          valid = false;
        } else {
          throw error;
        }
      }

      if ( !valid )
        return;

      if ( this.mode == 'edit' && !modified ) {
        this.returnToDetails( this.fileId );
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/file/' + this.mode + '.php', data, file ).then( ( { stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails( this.mode == 'add' ? stampId : this.fileId );
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    fileChange() {
      const files = this.$refs.file.files;
      if ( files.length > 0 ) {
        this.fileError = null;
        this.filePrompt = files[ 0 ].name;
        this.nameValue = files[ 0 ].name;
        this.$refs.name.focus();
      }
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails( itemId = null ) {
      if ( itemId != null )
        this.$router.push( 'IssueItem', { issueId: this.issueId, itemId } );
      else
        this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    },

    close() {
      this.$emit( 'close' );
    },

    handleDragDrop( e ) {
      if ( e.type == 'dragover' )
        this.dragOver = true;
      else
        this.dragOver = false;
    }
  },

  mounted() {
    if ( this.mode == 'add' ) {
      this.$refs.file.addEventListener( 'dragover', this.handleDragDrop );
      this.$refs.file.addEventListener( 'dragleave', this.handleDragDrop );
      this.$refs.file.addEventListener( 'drop', this.handleDragDrop );
    }
  },
  beforeDestroy() {
    if ( this.mode == 'add' ) {
      this.$refs.file.removeEventListener( 'dragover', this.handleDragDrop );
      this.$refs.file.removeEventListener( 'dragleave', this.handleDragDrop );
      this.$refs.file.removeEventListener( 'drop', this.handleDragDrop );
    }
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.form-upload {
  border: 1px dashed @btn-default-border;
  border-radius: @border-radius-base;
  min-height: 60px;
  position: relative;
  cursor: pointer;

  &:hover, &.drag-over {
    background-color: #f8f8f8;
    border-color: darken( @btn-default-border, 12% );
  }

  .has-error & {
    border-color: @state-danger-text;
  }

  > .form-control {
    opacity: 0;
    height: 58px;
    position: absolute;
    cursor: pointer;
  }

  > p {
    text-align: center;
    margin-top: 19px;
  }
}
</style>
