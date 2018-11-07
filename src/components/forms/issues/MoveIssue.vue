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
  <BaseForm v-bind:title="$t( 'cmd.MoveIssue' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt path="prompt.MoveIssue"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'label.Location' )" v-bind="$field( 'folderId' )">
      <LocationFilters ref="folderId" v-bind:typeId="typeId" v-bind:projectId.sync="projectId" v-bind:folderId.sync="folderId" require-admin folder-visible/>
    </FormGroup>
  </BaseForm>
</template>

<script>
export default {
  props: {
    issueId: Number,
    typeId: Number,
    initialFolderId: Number,
    name: String
  },

  fields() {
    return {
      folderId: {
        value: this.initialFolderId,
        type: Number,
        required: true,
        requiredError: this.$t( 'error.NoFolderSelected' )
      }
    };
  },

  data() {
    return {
      projectId: this.getInitialProjectId()
    };
  },

  methods: {
    getInitialProjectId() {
      if ( this.initialFolderId != null ) {
        const project = this.$store.state.global.projects.find( p => p.folders.some( f => f.id == this.initialFolderId ) );
        if ( project != null )
          return project.id;
      }
      return null;
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = { issueId: this.issueId, folderId: this.folderId };

      this.$form.block();

      this.$ajax.post( '/issues/move.php', data ).then( ( { stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    }
  }
}
</script>
