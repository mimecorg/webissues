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
  <div class="dropdown-filters">
    <DropdownButton ref="project" fa-class="fa-object-group" v-bind:text="projectName" v-bind:title="projectTitle">
      <div class="dropdown-menu-scroll">
        <li v-bind:class="{ active: project == null }">
          <HyperLink v-on:click="selectProject( null )">{{ $t( 'LocationFilters.SelectProject' ) }}</HyperLink>
        </li>
        <template v-if="availableProjects.length > 0">
          <li role="separator" class="divider"></li>
          <li v-for="p in availableProjects" v-bind:key="p.id" v-bind:class="{ active: project != null && p.id == project.id }">
            <HyperLink v-on:click="selectProject( p )">{{ p.name }}</HyperLink>
          </li>
        </template>
      </div>
    </DropdownButton>
    <DropdownButton v-if="folderVisible" ref="folder" fa-class="fa-folder-open-o" v-bind:text="folderName" v-bind:title="folderTitle">
      <div class="dropdown-menu-scroll">
        <li v-bind:class="{ active: folder == null }">
          <HyperLink v-on:click="selectFolder( null )">{{ $t( 'LocationFilters.SelectFolder' ) }}</HyperLink>
        </li>
        <template v-if="folders.length > 0">
          <li role="separator" class="divider"></li>
          <li v-for="f in folders" v-bind:key="f.id" v-bind:class="{ active: folder != null && f.id == folder.id }">
            <HyperLink v-on:click="selectFolder( f )">{{ f.name }}</HyperLink>
          </li>
        </template>
      </div>
    </DropdownButton>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    typeId: Number,
    project: Object,
    folder: Object,
    requireAdmin: Boolean,
    folderVisible: { type: Boolean, default: true }
  },

  computed: {
    ...mapState( 'global', [ 'userAccess', 'projects' ] ),
    availableProjects() {
      if ( this.requireAdmin && this.userAccess != Access.AdministratorAccess )
        return this.projects.filter( p => p.access == Access.AdministratorAccess );
      else
        return this.projects;
    },
    folders() {
      if ( this.project != null )
        return this.project.folders.filter( f => f.typeId == this.typeId );
      else
        return [];
    },
    projectName() {
      if ( this.project != null )
        return this.project.name;
      else
        return this.$t( 'LocationFilters.SelectProject' );
    },
    projectTitle() {
      if ( this.project != null )
        return this.$t( 'LocationFilters.ProjectTitle', [ this.projectName ] );
      else
        return this.$t( 'LocationFilters.SelectProject' );
    },
    folderName() {
      if ( this.folder != null )
        return this.folder.name;
      else
        return this.$t( 'LocationFilters.SelectFolder' );
    },
    folderTitle() {
      if ( this.folder != null )
        return this.$t( 'LocationFilters.FolderTitle', [ this.folderName ] );
      else
        return this.$t( 'LocationFilters.SelectFolder' );
    }
  },

  methods: {
    focus() {
      if ( this.project == null || !this.showFolder )
        this.$refs.project.focus();
      else
        this.$refs.folder.focus();
    },

    selectProject( project ) {
      this.$emit( 'select-project', project );
    },
    selectFolder( folder ) {
      this.$emit( 'select-folder', folder );
    }
  }
}
</script>
