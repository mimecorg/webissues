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
  <BaseForm v-bind:title="$t( 'cmd.RemoveProject' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt path="prompt.RemoveMember"><strong>{{ userName }}</strong><strong>{{ projectName }}</strong></Prompt>
  </BaseForm>
</template>

<script>
import { Access } from '@/constants'

export default {
  props: {
    userId: Number,
    projectId: Number,
    userName: String,
    projectName: String,
    accountMode: Boolean
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      this.$ajax.post( '/users/projects/edit.php', { userId: this.userId, projects: [ this.projectId ], access: Access.NoAccess } ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    returnToDetails() {
      if ( this.accountMode )
        this.$router.push( 'MyAccount' );
      else
        this.$router.push( 'UserDetails', { userId: this.userId } );
    }
  }
}
</script>
