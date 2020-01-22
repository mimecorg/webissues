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
    <DropdownScrollButton ref="project" fa-class="fa-briefcase" v-bind:text="projectName" v-bind:title="projectTitle">
      <li v-bind:class="{ active: project == null }">
        <HyperLink v-on:click="selectProject( null )">{{ projectLabel || $t( 'text.SelectProject' ) }}</HyperLink>
      </li>
      <template v-if="availableProjects.length > 0">
        <li role="separator" class="divider"></li>
        <li v-for="p in availableProjects" v-bind:key="p.id" v-bind:class="{ active: project != null && p.id == project.id }">
          <HyperLink v-on:click="selectProject( p )">{{ p.name }}</HyperLink>
        </li>
      </template>
    </DropdownScrollButton>
    <DropdownScrollButton v-if="folderVisible" ref="folder" fa-class="fa-folder-open-o" v-bind:text="folderName" v-bind:title="folderTitle">
      <li v-bind:class="{ active: folder == null }">
        <HyperLink v-on:click="selectFolder( null )">{{ folderLabel || $t( 'text.SelectFolder' ) }}</HyperLink>
      </li>
      <template v-if="availableFolders.length > 0">
        <li role="separator" class="divider"></li>
        <li v-for="f in availableFolders" v-bind:key="f.id" v-bind:class="{ active: folder != null && f.id == folder.id }">
          <HyperLink v-on:click="selectFolder( f )">{{ f.name }}</HyperLink>
        </li>
      </template>
    </DropdownScrollButton>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    typeId: Number,
    projectId: Number,
    folderId: Number,
    requireAdmin: Boolean,
    folderVisible: Boolean,
    projects: Array,
    projectLabel: String,
    folderLabel: String,
    autoExpand: Boolean
  },

  computed: {
    ...mapState( 'global', [ 'userAccess' ] ),
    availableProjects() {
      const projects = this.projects != null ? this.projects : this.$store.state.global.projects;
      if ( this.requireAdmin && this.userAccess != Access.AdministratorAccess )
        return projects.filter( p => p.access == Access.AdministratorAccess );
      else
        return projects;
    },
    availableFolders() {
      if ( this.project != null ) {
        if ( this.typeId != null )
          return this.project.folders.filter( f => f.typeId == this.typeId );
        else
          return this.project.folders;
      } else {
        return [];
      }
    },
    project() {
      if ( this.projectId != null )
        return this.availableProjects.find( p => p.id == this.projectId );
      else
        return null;
    },
    folder() {
      if ( this.folderId != null && this.project != null )
        return this.project.folders.find( f => f.id == this.folderId );
      else
        return null;
    },
    projectName() {
      if ( this.project != null )
        return this.project.name;
      else
        return this.projectLabel || this.$t( 'text.SelectProject' );
    },
    projectTitle() {
      if ( this.project != null )
        return this.$t( 'text.Project', [ this.projectName ] );
      else
        return this.projectLabel || this.$t( 'text.SelectProject' );
    },
    folderName() {
      if ( this.folder != null )
        return this.folder.name;
      else
        return this.folderLabel || this.$t( 'text.SelectFolder' );
    },
    folderTitle() {
      if ( this.folder != null )
        return this.$t( 'text.Folder', [ this.folderName ] );
      else
        return this.folderLabel || this.$t( 'text.SelectFolder' );
    }
  },

  methods: {
    focus() {
      if ( this.project == null || !this.folderVisible )
        this.$refs.project.focus();
      else
        this.$refs.folder.focus();
    },

    selectProject( project ) {
      if ( project != null )
        this.$emit( 'update:projectId', project.id );
      else
        this.$emit( 'update:projectId', null );
      if ( project == null || project.id != this.projectId )
        this.$emit( 'update:folderId', null );
      if ( this.autoExpand && this.folderVisible && project != null && project.id != this.projectId ) {
        this.$nextTick( () => {
          if ( this.availableFolders.length > 0 )
            this.$refs.folder.expand();
        } );
      }
    },

    selectFolder( folder ) {
      if ( folder != null )
        this.$emit( 'update:folderId', folder.id );
      else
        this.$emit( 'update:folderId', null );
    }
  }
}
</script>
