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
    <FormHeader v-bind:title="$t( 'title.UserAccounts' )" v-on:close="close">
      <button type="button" class="btn btn-success" v-on:click="addUser"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
    </FormHeader>
    <Grid v-bind:items="users" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-medium', 'column-medium', 'column-medium', null ]" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
  </div>
</template>

<script>
import { Access } from '@/constants'
import savePosition from '@/mixins/save-position'

export default {
  mixins: [ savePosition ],

  props: {
    users: Array
  },

  computed: {
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Login' ),
        this.$t( 'title.Email' ),
        this.$t( 'title.Access' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, user ) {
      switch ( columnIndex ) {
        case 0:
          return user.name;
        case 1:
          return user.login;
        case 2:
          return user.email;
        case 3:
          if ( user.access == Access.NoAccess )
            return this.$t( 'text.Disabled' );
          else if ( user.access == Access.NormalAccess )
            return this.$t( 'text.RegularUser' );
          else if ( user.access == Access.AdministratorAccess )
            return this.$t( 'text.SystemAdministrator' );
          break;
      }
    },

    addUser() {
      this.$router.push( 'AddUser' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'UserDetails', { userId: this.users[ rowIndex ].id } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
