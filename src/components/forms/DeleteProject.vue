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
    <Prompt v-if="mode == 'archive'" path="DeleteProject.ArchiveProjectPrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'delete'" path="DeleteProject.DeleteProjectPrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-if="mode == 'archive'" path="DeleteProject.DeleteProjectArchiveNote" alert-class="alert-success"><strong>{{ $t( 'DeleteProject.Note' ) }}</strong></Prompt>
    <Prompt v-else-if="mode == 'delete' && force" path="DeleteProject.DeleteProjectWarning" alert-class="alert-danger"><strong>{{ $t( 'DeleteProject.Warning' ) }}</strong></Prompt>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { ErrorCode } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    name: String,
    folders: Array
  },

  data() {
    return {
      force: this.folders != null && this.folders.length > 0
    };
  },

  computed: {
    title() {
      if ( this.mode == 'archive' )
        return this.$t( 'DeleteProject.ArchiveProject' );
      else if ( this.mode == 'delete' )
        return this.$t( 'DeleteProject.DeleteProject' );
    }
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      const data = { projectId: this.projectId };

      if ( this.mode == 'delete' )
        data.force = this.force;

      this.$ajax.post( '/server/api/project/' + this.mode + '.php', data ).then( () => {
        this.$store.commit( 'global/setDirty' );
        this.$router.push( 'ManageProjects' );
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.CannotDeleteProject ) {
          this.$emit( 'unblock' );
          this.force = true;
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      this.returnToDetails();
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
