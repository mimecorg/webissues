<!--
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
  <BaseForm v-bind:title="title" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt v-if="mode == 'archive'" path="prompt.ArchiveProject"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'delete'" path="prompt.DeleteProject"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'restore'" path="prompt.RestoreProject"><strong>{{ name }}</strong></Prompt>
    <Prompt v-if="mode == 'archive'" path="prompt.NoteArchiveProject" alert-class="alert-success"><strong>{{ $t( 'label.Note' ) }}</strong></Prompt>
    <Prompt v-else-if="mode == 'delete' && force" path="prompt.WarningDeleteProject" alert-class="alert-danger"><strong>{{ $t( 'label.Warning' ) }}</strong></Prompt>
  </BaseForm>
</template>

<script>
import { ErrorCode, Reason } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    name: String,
    folders: Array,
    archive: { type: Boolean, default: false }
  },

  data() {
    return {
      force: this.archive || this.folders != null && this.folders.length > 0
    };
  },

  computed: {
    title() {
      if ( this.mode == 'archive' )
        return this.$t( 'cmd.ArchiveProject' );
      else if ( this.mode == 'delete' )
        return this.$t( 'cmd.DeleteProject' );
      else if ( this.mode == 'restore' )
        return this.$t( 'cmd.RestoreProject' );
    }
  },

  methods: {
    submit() {
      this.$form.block();

      const data = { projectId: this.projectId };

      if ( this.mode == 'delete' )
        data.force = this.force;

      this.$ajax.post( '/projects/' + ( this.archive ? 'archive/' : '' ) + this.mode + '.php', data ).then( () => {
        this.$store.commit( 'global/setDirty' );
        if ( this.archive )
          this.$router.push( 'ProjectsArchive' );
        else
          this.$router.push( 'ManageProjects' );
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.CannotDeleteProject ) {
          this.$form.unblock();
          this.force = true;
        } else {
          this.$form.error( error );
        }
      } );
    },

    returnToDetails() {
      if ( this.archive )
        this.$router.push( 'ProjectDetailsArchive', { projectId: this.projectId } );
      else
        this.$router.push( 'ProjectDetails', { projectId: this.projectId } );
    }
  }
}
</script>
