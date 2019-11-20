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
  <BaseForm v-bind:title="$t( 'title.UserAccounts' )" size="large" auto-close save-position>
    <template v-slot:header>
      <button type="button" class="btn btn-success" v-on:click="addUser"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
      <button v-if="hasRegistrationRequests" type="button" class="btn btn-default" v-on:click="registrationRequests">
        <span class="fa fa-user-plus" aria-hidden="true"></span> {{ $t( 'title.RegistrationRequests' ) }}
      </button>
    </template>
    <Grid v-bind:items="users" v-bind:columns="columns" v-on:row-click="rowClick">
      <template v-slot:access-cell="{ item: user }">
        {{ getAccess( user ) }}
      </template>
    </Grid>
  </BaseForm>
</template>

<script>
import { Access } from '@/constants'

export default {
  props: {
    users: Array
  },

  computed: {
    columns() {
      return {
        name: { title: this.$t( 'title.Name' ), class: 'column-medium' },
        login: { title: this.$t( 'title.Login' ), class: 'column-medium' },
        email: { title: this.$t( 'title.Email' ), class: 'column-medium' },
        access: { title: this.$t( 'title.Access' ) }
      };
    },
    hasRegistrationRequests() {
      return this.$store.state.global.settings.selfRegister && !this.$store.state.global.settings.registerAutoApprove;
    }
  },

  methods: {
    getAccess( user ) {
      if ( user.access == Access.NoAccess )
        return this.$t( 'text.Disabled' );
      else if ( user.access == Access.NormalAccess )
        return this.$t( 'text.RegularUser' );
      else if ( user.access == Access.AdministratorAccess )
        return this.$t( 'text.SystemAdministrator' );
    },

    addUser() {
      this.$router.push( 'AddUser' );
    },

    registrationRequests() {
      this.$router.push( 'RegistrationRequests' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'UserDetails', { userId: this.users[ rowIndex ].id } );
    }
  }
}
</script>
