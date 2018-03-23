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
    <Prompt v-if="mode == 'rename'" path="EditProject.RenameProjectPrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditProject.AddProjectPrompt"></Prompt>
    <FormGroup id="name" v-bind:label="$t( 'EditProject.Name' )" v-bind:required="true" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="maxLength" v-model="nameValue">
    </FormGroup>
    <MarkupEditor v-if="mode == 'add'" ref="description" id="description" v-bind:label="$t( 'EditProject.Description' )" v-bind:error="descriptionError"
                  v-bind:format="selectedFormat" v-model="descriptionValue" v-on:select-format="selectFormat" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength, ErrorCode } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    name: String,
    descriptionFormat: Number
  },

  data() {
    return {
      nameValue: this.name,
      nameError: null,
      maxLength: MaxLength.Name,
      descriptionValue: '',
      selectedFormat: this.descriptionFormat,
      descriptionError: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'settings' ] ),
    title() {
      if ( this.mode == 'rename' )
        return this.$t( 'EditProject.RenameProject' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditProject.AddProject' );
    }
  },

  methods: {
    selectFormat( format ) {
      this.selectedFormat = format;
    },

    submit() {
      this.nameError = null;
      this.descriptionError = null;

      const data = {};
      let modified = false;
      let valid = true;

      if ( this.mode == 'rename' )
        data.projectId = this.projectId;

      try {
        this.nameValue = this.$parser.normalizeString( this.nameValue, MaxLength.Name );
        if ( this.mode == 'add' || this.nameValue != this.name ) {
          data.name = this.nameValue;
          modified = true;
        }
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

      if ( this.mode == 'add' ) {
        try {
          this.descriptionValue = this.$parser.normalizeString( this.descriptionValue, this.settings.commentMaxLength, { allowEmpty: true, multiLine: true } );
          if ( this.mode == 'add' || this.descriptionValue != this.description ) {
            modified = true;
            data.description = this.descriptionValue;
            data.descriptionFormat = this.selectedFormat;
          }
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
      }

      if ( !valid )
        return;

      if ( this.mode == 'rename' && !modified ) {
        this.returnToDetails( this.projectId );
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/projects/' + this.mode + '.php', data ).then( ( { projectId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails( projectId );
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.ProjectAlreadyExists ) {
          this.$emit( 'unblock' );
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      if ( this.mode == 'rename' )
        this.returnToDetails( this.projectId );
      else
        this.$router.push( 'ManageProjects' );
    },

    returnToDetails( projectId ) {
      this.$router.push( 'ProjectDetails', { projectId } );
    },

    close() {
      this.$emit( 'close' );
    },
    error( error ) {
      this.$emit( 'error', error );
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
