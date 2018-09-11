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
    <FormHeader v-bind:title="$t( 'cmd.RemoveMember' )" v-on:close="close"/>
    <Prompt path="prompt.RemoveMember"><strong>{{ userName }}</strong><strong>{{ projectName }}</strong></Prompt>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { Access } from '@/constants'

export default {
  props: {
    projectId: Number,
    userId: Number,
    projectName: String,
    userName: String
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      this.$ajax.post( '/server/api/projects/members/edit.php', { projectId: this.projectId, users: [ this.userId ], access: Access.NoAccess } ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ProjectPermissions', { projectId: this.projectId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
