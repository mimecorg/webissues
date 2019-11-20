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
  <BaseForm v-bind:title="$t( 'title.Projects' )" auto-close save-position>
    <template v-slot:header>
      <button v-if="isAdministrator" type="button" class="btn btn-success" v-on:click="addProject"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
      <button v-if="isAdministrator" type="button" class="btn btn-default" v-on:click="projectsArchive"><span class="fa fa-archive" aria-hidden="true"></span> {{ $t( 'title.Archive' ) }}</button>
    </template>
    <Grid v-if="projects.length > 0" v-bind:items="projects" v-bind:columns="columns" v-on:row-click="rowClick">
      <template v-slot:access-cell="{ item: project }">
        {{ project.public ? $t( 'text.PublicProject' ) : $t( 'text.RegularProject' ) }}
      </template>
    </Grid>
    <Prompt v-else v-bind:path="isAdministrator ? 'info.NoProjects' : 'info.NoAvailableProjects'"/>
  </BaseForm>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  props: {
    projects: Array
  },

  computed: {
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    columns() {
      return {
        name: { title: this.$t( 'title.Name' ), class: 'column-large' },
        access: { title: this.$t( 'title.Access' ) }
      };
    }
  },

  methods: {
    addProject() {
      this.$router.push( 'AddProject' );
    },
    projectsArchive() {
      this.$router.push( 'ProjectsArchive' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'ProjectDetails', { projectId: this.projects[ rowIndex ].id } );
    }
  }
}
</script>
