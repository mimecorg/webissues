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
    <Prompt v-if="mode == 'edit'" path="EditFile.EditFilePrompt"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditFile.AddFilePrompt"><strong>{{ issueName }}</strong></Prompt>
    <FormGroup v-if="mode == 'add'" v-bind:error="fileError">
      <div v-bind:class="[ 'form-upload', { 'drag-over': dragOver } ]">
        <input ref="file" id="file" type="file" class="form-control" v-on:change="fileChange">
        <p>{{ filePrompt }}</p>
      </div>
    </FormGroup>
    <FormInput ref="name" id="name" v-bind:label="$t( 'EditFile.FileName' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormInput ref="description" id="description" v-bind:label="$t( 'EditFile.Description' )" v-bind="$field( 'description' )" v-model="description"/>
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
    initialName: String,
    initialDescription: String
  },

  fields() {
    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.FileName,
        parse: this.parseName
      },
      description: {
        value: this.initialDescription,
        type: String,
        required: false,
        maxLength: MaxLength.FileDescription
      }
    };
  },

  data() {
    return {
      filePrompt: this.$t( 'EditFile.FilePrompt' ),
      fileError: null,
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

      let file = null;

      if ( this.mode == 'add' ) {
        if ( this.$refs.file.files.length == 0 ) {
          this.fileError = this.$t( 'EditFile.NoFileSelected' );
        } else {
          file = this.$refs.file.files[ 0 ];
          if ( file.size > this.settings.fileMaxSize )
            this.fileError = this.$t( 'EditFile.FileTooLarge' );
        }
      }

      if ( !this.$fields.validate() || this.fileError != null )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails( this.fileId );
        return;
      }

      const data = {};
      if ( this.mode == 'add' )
        data.issueId = this.issueId;
      else
        data.fileId = this.fileId;
      data.name = this.name;
      data.description = this.description;

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issues/files/' + this.mode + '.php', data, file ).then( ( { stampId } ) => {
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
        this.name = files[ 0 ].name;
        this.$refs.name.focus();
      }
    },

    parseName( value ) {
      if ( value.charAt( 0 ) == '.' || /[\\/:*?"<>|]/.test( value ) )
        throw this.$fields.makeError( this.$t( 'EditFile.InvalidFileName' ) );
      return value;
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
    } else {
      this.$refs.name.focus();
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
