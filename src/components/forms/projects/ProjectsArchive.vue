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
  <BaseForm v-bind:title="$t( 'title.ProjectsArchive' )" v-bind:breadcrumbs="breadcrumbs" auto-close save-position>
    <Grid v-if="projects.length > 0" v-bind:items="projects" v-bind:columns="columns" v-on:row-click="rowClick"/>
    <Prompt v-else path="info.NoArchivedProjects"/>
  </BaseForm>
</template>

<script>
export default {
  props: {
    projects: Array
  },

  computed: {
    breadcrumbs() {
      return [
        { label: this.$t( 'title.Projects' ), route: 'ManageProjects' }
      ];
    },
    columns() {
      return {
        name: { title: this.$t( 'title.Name' ), class: 'column-large' }
      };
    }
  },

  methods: {
    rowClick( rowIndex ) {
      this.$router.push( 'ProjectDetailsArchive', { projectId: this.projects[ rowIndex ].id } );
    }
  }
}
</script>
