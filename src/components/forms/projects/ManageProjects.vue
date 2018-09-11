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
    <FormHeader v-bind:title="$t( 'title.Projects' )" v-on:close="close">
      <button v-if="isAdministrator" type="button" class="btn btn-success" v-on:click="addProject"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
    </FormHeader>
    <Grid v-if="projects.length > 0" v-bind:items="projects" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-wide', null ]" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
    <Prompt v-else v-bind:path="isAdministrator ? 'info.NoProjects' : 'info.NoAvailableProjects'"/>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  props: {
    projects: Array
  },

  computed: {
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Access' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, project ) {
      switch ( columnIndex ) {
        case 0:
          return project.name;
        case 1:
          return project.public ? this.$t( 'text.PublicProject' ) : this.$t( 'text.RegularProject' );
      }
    },

    addProject() {
      this.$router.push( 'AddProject' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'ProjectDetails', { projectId: this.projects[ rowIndex ].id } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
