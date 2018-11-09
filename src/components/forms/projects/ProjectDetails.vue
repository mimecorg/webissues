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
    <template slot="header">
      <button v-if="isProjectAdministrator" type="button" class="btn btn-default" v-on:click="projectPermissions"><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'title.Permissions' ) }}</button>
      <DropdownButton v-if="isAdministrator" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="renameProject"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.RenameProject' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="archiveProject"><span class="fa fa-archive" aria-hidden="true"></span> {{ $t( 'cmd.ArchiveProject' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteProject"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.DeleteProject' ) }}</HyperLink></li>
      </DropdownButton>
    </template>
    <FormSection v-bind:title="$t( 'title.Description' )">
      <DropdownButton v-if="isProjectAdministrator && description != null" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.Menu' )">
        <li><HyperLink v-on:click="editDescription"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteDescription"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}</HyperLink></li>
      </DropdownButton>
      <button v-else-if="isProjectAdministrator" type="button" class="btn btn-default" v-bind:title="$t( 'cmd.AddDescription' )" v-on:click="addDescription">
        <span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
      </button>
    </FormSection>
    <div v-if="description != null" class="description-panel">
      <div class="formatted-text" v-hljs="description.text"></div>
        <div class="last-edited">
          <span class="fa fa-pencil" aria-hidden="true"></span> {{ descriptionModifiedDate }} &mdash; {{ descriptionModifiedByName }}
        </div>
    </div>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoProjectDescription' ) }}
    </div>
    <FormSection v-bind:title="$t( 'title.Folders' )">
      <button v-if="isProjectAdministrator" type="button" class="btn btn-success" v-bind:title="$t( 'cmd.AddFolder' )" v-on:click="addFolder">
        <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
      </button>
    </FormSection>
    <Grid v-if="folders.length > 0" v-bind:items="folders" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-large', null ]"
          v-bind:row-click-disabled="!isProjectAdministrator" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass, columnKey }">
        <td v-bind:key="columnKey" v-bind:class="columnClass" v-html="getCellValue( columnIndex, item )"></td>
      </template>
    </Grid>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoFolders' ) }}
    </div>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    projectId: Number,
    name: String,
    access: Number,
    description: Object,
    folders: Array
  },

  computed: {
    ...mapState( 'global', [ 'types', 'users' ] ),
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    breadcrumbs() {
      return [
        { label: this.$t( 'title.Projects' ), route: 'ManageProjects' }
      ];
    },
    isProjectAdministrator() {
      return this.access == Access.AdministratorAccess;
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
    },
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Type' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, folder ) {
      switch ( columnIndex ) {
        case 0:
          return folder.name;
        case 1:
          const type = this.types.find( t => t.id == folder.typeId );
          if ( type != null )
            return type.name;
          break;
      }
    },

    renameProject() {
      this.$router.push( 'RenameProject', { projectId: this.projectId } );
    },
    archiveProject() {
      this.$router.push( 'ArchiveProject', { projectId: this.projectId } );
    },
    deleteProject() {
      this.$router.push( 'DeleteProject', { projectId: this.projectId } );
    },
    projectPermissions() {
      this.$router.push( 'ProjectPermissions', { projectId: this.projectId } );
    },

    addDescription() {
      this.$router.push( 'AddProjectDescription', { projectId: this.projectId } );
    },
    editDescription() {
      this.$router.push( 'EditProjectDescription', { projectId: this.projectId } );
    },
    deleteDescription() {
      this.$router.push( 'DeleteProjectDescription', { projectId: this.projectId } );
    },

    addFolder() {
      this.$router.push( 'AddFolder', { projectId: this.projectId } );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'RenameFolder', { projectId: this.projectId, folderId: this.folders[ rowIndex ].id } );
    }
  }
}
</script>
