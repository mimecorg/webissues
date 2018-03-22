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
    <FormHeader v-bind:title="$t( 'MoveIssue.MoveIssue' )" v-on:close="close"/>
    <Prompt path="MoveIssue.MoveIssuePrompt"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'MoveIssue.Location' )" v-bind:required="true" v-bind:error="locationError">
      <LocationFilters ref="location" v-bind:typeId="typeId" v-bind:project="project" v-bind:folder="folder" v-bind:require-admin="true"
                       v-on:select-project="selectProject" v-on:select-folder="selectFolder"/>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    issueId: Number,
    typeId: Number,
    projectId: Number,
    folderId: Number,
    name: String
  },

  data() {
    return {
      selectedProjectId: this.projectId,
      selectedFolderId: this.folderId,
      locationError: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] ),
    project() {
      if ( this.selectedProjectId != null )
        return this.projects.find( p => p.id == this.selectedProjectId );
      else
        return null;
    },
    folder() {
      if ( this.selectedFolderId != null && this.project != null )
        return this.project.folders.find( f => f.id == this.selectedFolderId );
      else
        return null;
    }
  },

  methods: {
    selectProject( project ) {
      if ( project != null )
        this.selectedProjectId = project.id;
      else
        this.selectedProjectId = null;
      this.selectedFolderId = null;
    },
    selectFolder( folder ) {
      if ( folder != null )
        this.selectedFolderId = folder.id;
      else
        this.selectedFolderId = null;
    },

    submit() {
      this.locationError = null;

      const data = { issueId: this.issueId };
      let modified = false;
      let valid = true;

      if ( this.selectedFolderId != null ) {
        if ( this.selectedFolderId != this.folderId )
          modified = true;
        data.folderId = this.selectedFolderId;
      } else {
        this.locationError = this.$t( 'MoveIssue.NoFolderSelected' );
        if ( valid )
          this.$refs.location.focus();
        valid = false;
      }

      if ( !valid )
        return;

      if ( !modified ) {
        this.returnToDetails();
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/move.php', data ).then( ( { stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
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
    }
  }
}
</script>
