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
  <BaseForm v-bind:title="name" v-bind:breadcrumbs="breadcrumbs" auto-close save-position>
    <template v-slot:header>
      <DropdownButton fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="restoreProject"><span class="fa fa-undo" aria-hidden="true"></span> {{ $t( 'cmd.RestoreProject' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="renameProject"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.RenameProject' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteProject"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.DeleteProject' ) }}</HyperLink></li>
      </DropdownButton>
    </template>
    <FormSection v-bind:title="$t( 'title.Description' )"/>
    <div v-if="description != null" class="description-panel">
      <div class="formatted-text" v-hljs="description.text"></div>
        <div class="last-edited">
          <span class="fa fa-pencil" aria-hidden="true"></span> {{ descriptionModifiedDate }} &mdash; {{ descriptionModifiedByName }}
        </div>
    </div>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoProjectDescription' ) }}
    </div>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    projectId: Number,
    name: String,
    description: Object
  },

  computed: {
    ...mapState( 'global', [ 'users' ] ),
    breadcrumbs() {
      return [
        { label: this.$t( 'title.Projects' ), route: 'ManageProjects' },
        { label: this.$t( 'title.ProjectsArchive' ), route: 'ProjectsArchive' }
      ];
    },
    descriptionModifiedByName() {
      if ( this.description != null ) {
        const user = this.users.find( u => u.id == this.description.modifiedBy );
        if ( user != null )
          return user.name;
      }
      return null;
    },
    descriptionModifiedDate() {
      if ( this.description != null )
        return this.$formatter.formatStamp( this.description.modifiedDate );
      else
        return null;
    }
  },

  methods: {
    restoreProject() {
      this.$router.push( 'RestoreProject', { projectId: this.projectId } );
    },
    renameProject() {
      this.$router.push( 'RenameProjectArchive', { projectId: this.projectId } );
    },
    deleteProject() {
      this.$router.push( 'DeleteProjectArchive', { projectId: this.projectId } );
    }
  }
}
</script>
