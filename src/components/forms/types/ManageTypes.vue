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
  <BaseForm v-bind:title="$t( 'title.IssueTypes' )" auto-close save-position>
    <template slot="header">
      <button v-if="isAdministrator" type="button" class="btn btn-success" v-on:click="addType"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
    </template>
    <Grid v-if="types.length > 0" v-bind:items="types" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-large' ]" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
    <Prompt v-else path="info.NoIssueTypes"/>
  </BaseForm>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  props: {
    types: Array
  },

  computed: {
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    columnNames() {
      return [ this.$t( 'title.Name' ) ];
    }
  },

  methods: {
    getCellValue( columnIndex, type ) {
      switch ( columnIndex ) {
        case 0:
          return type.name;
      }
    },

    addType() {
      this.$router.push( 'AddType' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'TypeDetails', { typeId: this.types[ rowIndex ].id } );
    }
  }
}
</script>
