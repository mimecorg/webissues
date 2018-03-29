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
    <Prompt v-if="mode == 'rename'" path="EditProject.RenameProjectPrompt"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditProject.AddProjectPrompt"></Prompt>
    <FormGroup id="name" v-bind:label="$t( 'EditProject.Name' )" v-bind:required="nameRequired" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="nameMaxLength" v-model="name">
    </FormGroup>
    <MarkupEditor v-if="mode == 'add'" ref="description" id="description" v-bind:label="$t( 'EditProject.Description' )" v-bind:required="descriptionRequired"
                  v-bind:error="descriptionError" v-bind:format="descriptionFormat" v-model="description" v-on:select-format="selectFormat" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { MaxLength, ErrorCode } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    initialName: String,
    initialFormat: Number
  },

  fields() {
    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name
      },
      description: {
        condition: this.mode == 'add',
        type: String,
        required: false,
        maxLength: this.$store.state.global.settings.commentMaxLength,
        multiLine: true
      }
    };
  },

  data() {
    return {
      descriptionFormat: this.initialFormat
    };
  },

  computed: {
    title() {
      if ( this.mode == 'rename' )
        return this.$t( 'EditProject.RenameProject' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditProject.AddProject' );
    }
  },

  methods: {
    selectFormat( format ) {
      this.descriptionFormat = format;
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'rename' && !this.$fields.modified() ) {
        this.returnToDetails( this.projectId );
        return;
      }

      const data = {};
      if ( this.mode == 'rename' )
        data.projectId = this.projectId;
      data.name = this.name;
      if ( this.mode == 'add' ) {
        data.description = this.description;
        data.descriptionFormat = this.descriptionFormat;
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
