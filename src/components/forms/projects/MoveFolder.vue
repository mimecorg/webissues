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
  <BaseForm v-bind:title="$t( 'cmd.MoveFolder' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt path="prompt.MoveFolder"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'label.Project' )" v-bind="$field( 'projectId' )">
      <LocationFilters ref="projectId" v-bind:projectId.sync="projectId" require-admin/>
    </FormGroup>
  </BaseForm>
</template>

<script>
import { ErrorCode, Reason } from '@/constants'

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
        requiredError: this.$t( 'error.NoProjectSelected' )
      }
    };
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = { folderId: this.folderId, projectId: this.projectId };

      this.$form.block();

      this.$ajax.post( '/projects/folders/move.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.FolderAlreadyExists ) {
          this.$form.unblock();
          this.projectIdError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.projectId.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.initialProjectId } );
    }
  }
}
</script>
