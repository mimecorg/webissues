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
    <FormHeader v-bind:title="$t( 'title.RegistrationRequests' )" v-bind:breadcrumbs="breadcrumbs" v-on:close="close"/>
    <Grid v-if="requests.length > 0" v-bind:items="requests" v-bind:column-names="columnNames"
          v-bind:column-classes="[ 'column-medium', 'column-medium', 'column-medium', null ]" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
    <Prompt v-else path="info.NoRegistrationRequests"/>
  </div>
</template>

<script>
import savePosition from '@/mixins/save-position'

export default {
  mixins: [ savePosition ],

  props: {
    requests: Array
  },

  computed: {
    breadcrumbs() {
      return [
        { label: this.$t( 'title.UserAccounts' ), route: 'ManageUsers' }
      ];
    },
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Login' ),
        this.$t( 'title.Email' ),
        this.$t( 'title.CreatedDate' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, request ) {
      switch ( columnIndex ) {
        case 0:
          return request.name;
        case 1:
          return request.login;
        case 2:
          return request.email;
        case 3:
          return this.$formatter.formatStamp( request.date );
      }
    },

    rowClick( rowIndex ) {
      this.$router.push( 'RequestDetails', { requestId: this.requests[ rowIndex ].id } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
