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
    <FormHeader v-bind:title="$t( 'MoveFolder.MoveFolder' )" v-on:close="close"/>
    <Prompt path="MoveFolder.MoveFolderPrompt"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'MoveFolder.Project' )" v-bind="$field( 'projectId' )">
      <LocationFilters ref="projectId" v-bind:project="project" require-admin v-on:select-project="selectProject"/>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { ErrorCode } from '@/constants'

export default {
  props: {
    initialProjectId: Number,
    folderId: Number,
    name: String
  },

  fields() {
    return {
      projectId: {
        value: this.initialProjectId,
        type: Number,
        required: true,
        requiredError: this.$t( 'MoveFolder.NoProjectSelected' )
      }
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] ),
    project() {
      if ( this.projectId != null )
        return this.projects.find( p => p.id == this.projectId );
      else
        return null;
    }
  },

  methods: {
    selectProject( project ) {
      if ( project != null )
        this.projectId = project.id;
      else
        this.projectId = null;
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = { folderId: this.folderId, projectId: this.projectId };

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/projects/folders/move.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.FolderAlreadyExists ) {
          this.$emit( 'unblock' );
          this.projectIdError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.projectId.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      this.$router.push( 'RenameFolder', { projectId: this.initialProjectId, folderId: this.folderId } );
    },

    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.initialProjectId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
