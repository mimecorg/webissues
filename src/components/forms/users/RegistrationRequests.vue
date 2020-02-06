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
  <BaseForm v-bind:title="$t( 'title.RegistrationRequests' )" v-bind:breadcrumbs="breadcrumbs" v-bind:size="size" auto-close save-position>
    <Grid v-if="requests.length > 0" v-bind:items="requests" v-bind:columns="columns" v-on:row-click="rowClick">
      <template v-slot:date-cell="{ item: request }">
        {{ $formatter.formatStamp( request.date ) }}
      </template>
    </Grid>
    <Prompt v-else path="info.NoRegistrationRequests"/>
  </BaseForm>
</template>

<script>
export default {
  props: {
    requests: Array
  },

  computed: {
    breadcrumbs() {
      return [
        { label: this.$t( 'title.UserAccounts' ), route: 'ManageUsers' }
      ];
    },
    size() {
      if ( this.requests.length > 0 )
        return 'large';
      else
        return 'normal';
    },
    columns() {
      return {
        name: { title: this.$t( 'title.Name' ), class: 'column-medium' },
        login: { title: this.$t( 'title.Login' ), class: 'column-medium' },
        email: { title: this.$t( 'title.Email' ), class: 'column-medium' },
        date: { title: this.$t( 'title.CreatedDate' ) }
      };
    }
  },

  methods: {
    rowClick( rowIndex ) {
      this.$router.push( 'RequestDetails', { requestId: this.requests[ rowIndex ].id } );
    }
  }
}
</script>
