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
    <FormHeader v-bind:title="$t( 'DeleteFolder.DeleteFolder' )" v-on:close="close"/>
    <Prompt path="DeleteFolder.DeleteFolderPrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-if="force" path="DeleteFolder.DeleteFolderWarning" alert-class="alert-danger"><strong>{{ $t( 'DeleteFolder.Warning' ) }}</strong></Prompt>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { ErrorCode } from '@/constants'

export default {
  props: {
    projectId: Number,
    folderId: Number,
    name: String,
    empty: Boolean
  },

  data() {
    return {
      force: !this.empty
    };
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      const data = { folderId: this.folderId, force: this.force };

      this.$ajax.post( '/server/api/projects/folders/delete.php', data ).then( () => {
        this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.CannotDeleteFolder ) {
          this.$emit( 'unblock' );
          this.force = true;
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      this.$router.push( 'RenameFolder', { projectId: this.projectId, folderId: this.folderId } );
    },

    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.projectId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
