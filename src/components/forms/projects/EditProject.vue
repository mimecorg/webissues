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
  <BaseForm v-bind:title="title" v-bind:size="size" with-buttons v-on:ok="submit" v-on:cancel="cancel">
    <Prompt v-if="mode == 'rename'" path="prompt.RenameProject"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddProject"></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <MarkupEditor v-if="mode == 'add'" ref="description" id="description" v-bind:label="$t( 'label.Description' )" v-bind="$field( 'description' )"
                  v-bind:format.sync="descriptionFormat" v-model="description"/>
  </BaseForm>
</template>

<script>
import { MaxLength, ErrorCode, Reason } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    initialName: String,
    initialFormat: Number,
    archive: { type: Boolean, default: false }
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
      },
      descriptionFormat: {
        value: this.initialFormat,
        type: Number
      }
    };
  },

  computed: {
    title() {
      if ( this.mode == 'rename' )
        return this.$t( 'cmd.RenameProject' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddProject' );
    },
    size() {
      if ( this.mode == 'rename' )
        return 'small';
      else if ( this.mode == 'add' )
        return 'normal';
    }
  },

  methods: {
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

      this.$form.block();

      this.$ajax.post( '/projects/' + ( this.archive ? 'archive/' : '' ) + this.mode + '.php', data ).then( ( { projectId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails( projectId );
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.ProjectAlreadyExists ) {
          this.$form.unblock();
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    cancel() {
      if ( this.mode == 'rename' )
        this.returnToDetails( this.projectId );
      else if ( this.archive )
        this.$router.push( 'ManageProjectsArchive' );
      else
        this.$router.push( 'ManageProjects' );
    },

    returnToDetails( projectId ) {
      if ( this.archive )
        this.$router.push( 'ProjectDetailsArchive', { projectId } );
      else
        this.$router.push( 'ProjectDetails', { projectId } );
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
