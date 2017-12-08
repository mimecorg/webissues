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
  <div id="main-toolbar">
    <div class="container-fluid">
      <div class="row">
        <div class="col-xs-12 col-sm-6 col-lg-4 main-filters">
          <dropdown-button v-if="types.length > 0" fa-class="fa-list" v-bind:text="typeName" v-bind:title="typeTitle">
            <div class="dropdown-menu-scroll">
              <li v-for="t in types" v-bind:key="t.id" v-bind:class="{ active: type != null && t.id == type.id }">
                <action-link v-on:click="selectType( t )">{{ t.name }}</action-link>
              </li>
            </div>
          </dropdown-button>
          <dropdown-button v-if="type != null" fa-class="fa-filter" v-bind:text="viewName" v-bind:title="viewTitle">
            <div class="dropdown-menu-scroll">
              <li v-bind:class="{ active: view == null }">
                <action-link v-on:click="selectView( null )">{{ $t( 'main.all_issues' ) }}</action-link>
              </li>
              <template v-if="personalViews.length > 0">
                <li role="separator" class="divider"></li>
                <li class="dropdown-header">{{ $t( 'main.personal_views' ) }}</li>
                <li v-for="v in personalViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
                  <action-link v-on:click="selectView( v )">{{ v.name }}</action-link>
                </li>
              </template>
              <template v-if="publicViews.length > 0">
                <li role="separator" class="divider"></li>
                <li class="dropdown-header">{{ $t( 'main.public_views' ) }}</li>
                <li v-for="v in publicViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
                  <action-link v-on:click="selectView( v )">{{ v.name }}</action-link>
                </li>
              </template>
            </div>
          </dropdown-button>
        </div>
        <div v-if="type != null" class="col-xs-12 col-sm-6 col-lg-4 main-filters">
          <dropdown-button fa-class="fa-object-group" v-bind:text="projectName" v-bind:title="projectTitle">
            <div class="dropdown-menu-scroll">
              <li v-bind:class="{ active: project == null }">
                <action-link v-on:click="selectProject( null )">{{ $t( 'main.all_projects' ) }}</action-link>
              </li>
              <template v-if="projects.length > 0">
                <li role="separator" class="divider"></li>
                <li v-for="p in projects" v-bind:key="p.id" v-bind:class="{ active: project != null && p.id == project.id }">
                  <action-link v-on:click="selectProject( p )">{{ p.name }}</action-link>
                </li>
              </template>
            </div>
          </dropdown-button>
          <dropdown-button fa-class="fa-folder-open-o" v-bind:text="folderName" v-bind:title="folderTitle">
            <div class="dropdown-menu-scroll">
              <li v-bind:class="{ active: folder == null }">
                <action-link v-on:click="selectFolder( null )">{{ $t( 'main.all_folders' ) }}</action-link>
              </li>
              <template v-if="folders.length > 0">
                <li role="separator" class="divider"></li>
                <li v-for="f in folders" v-bind:key="f.id" v-bind:class="{ active: folder != null && f.id == folder.id }">
                  <action-link v-on:click="selectFolder( f )">{{ f.name }}</action-link>
                </li>
              </template>
            </div>
          </dropdown-button>
        </div>
        <div v-if="type != null" class="col-xs-12 col-lg-4">
          <div class="main-group">
            <div class="main-element main-element-wide">
              <div class="input-group" v-bind:class="{ 'has-error': searchError }">
                <dropdown-button class="input-group-btn" fa-class="fa-chevron-down" v-bind:title="searchTitle">
                  <div class="dropdown-menu-scroll">
                    <li v-for="c in systemColumns" v-bind:class="{ active: isSearchColumn( c ) }">
                      <action-link v-on:click="setSearchColumn( c )">{{ c.name }}</action-link>
                    </li>
                    <template v-if="type.attributes.length > 0">
                      <li role="separator" class="divider"></li>
                      <li v-for="a in type.attributes" v-bind:class="{ active: isSearchAttribute( a ) }">
                        <action-link v-on:click="setSearchAttribute( a )">{{ a.name }}</action-link>
                      </li>
                    </template>
                  </div>
                </dropdown-button>
                <input ref="search" type="search" class="form-control" v-bind:placeholder="searchName" v-bind:maxlength="searchLength"
                       v-bind:value="searchText" v-on:keydown.enter="search">
                <div class="input-group-btn">
                  <button type="button" class="btn btn-default" v-bind:title="$t( 'main.search' )" v-on:click="search"><span class="fa fa-search" aria-hidden="true"></span></button>
                </div>
              </div>
            </div>
            <div class="main-element">
              <button type="button" class="btn btn-default" v-bind:title="$t( 'main.reload' )" v-on:click="reload"><span class="fa fa-refresh" aria-hidden="true"></span></button>
              <dropdown-button fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'main.more' )">
                <li><action-link><span class="fa fa-check-circle-o" aria-hidden="true"></span> {{ $t( 'main.mark_all_as_read' ) }}</action-link></li>
                <li><action-link><span class="fa fa-check-circle" aria-hidden="true"></span> {{ $t( 'main.mark_all_as_unread' ) }}</action-link></li>
                <li role="separator" class="divider"></li>
                <li><action-link><span class="fa fa-pencil-square-o" aria-hidden="true"></span> {{ $t( 'main.project_description' ) }}</action-link></li>
                <li role="separator" class="divider"></li>
                <li><action-link><span class="fa fa-file-text-o" aria-hidden="true"></span> {{ $t( 'main.export_to_csv' ) }}</action-link></li>
              </dropdown-button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Column, MaxLength } from '@/constants'

export default {
  data() {
    return {
      searchLength: MaxLength.Value
    };
  },
  computed: {
    ...mapState( 'global', [ 'types', 'projects' ] ),
    ...mapState( 'list', [ 'searchColumn', 'searchText', 'searchError' ] ),
    ...mapGetters( 'list', [ 'type', 'view', 'publicViews', 'personalViews', 'project', 'folder', 'folders' ] ),
    typeName() {
      if ( this.type != null )
        return this.type.name;
      else
        return this.$t( 'main.select_type' );
    },
    typeTitle() {
      if ( this.type != null )
        return this.$t( 'main.type_title', [ this.type.name ] );
      else
        return this.$t( 'main.select_type' );
    },
    viewName() {
      if ( this.view != null )
        return this.view.name;
      else
        return this.$t( 'main.all_issues' );
    },
    viewTitle() {
      if ( this.view != null )
        return this.$t( 'main.view_title', [ this.viewName ] );
      else
        return this.$t( 'main.all_issues' );
    },
    projectName() {
      if ( this.project != null )
        return this.project.name;
      else
        return this.$t( 'main.all_projects' );
    },
    projectTitle() {
      if ( this.project != null )
        return this.$t( 'main.project_title', [ this.projectName ] );
      else
        return this.$t( 'main.all_projects' );
    },
    folderName() {
      if ( this.folder != null )
        return this.folder.name;
      else
        return this.$t( 'main.all_folders' );
    },
    folderTitle() {
      if ( this.folder != null )
        return this.$t( 'main.folder_title', [ this.folderName ] );
      else
        return this.$t( 'main.all_folders' );
    },
    systemColumns() {
      return [
        { id: Column.ID, name: this.$t( 'main.id' ) },
        { id: Column.Name, name: this.$t( 'main.name' ) },
        { id: Column.CreatedDate, name: this.$t( 'main.created_date' ) },
        { id: Column.CreatedBy, name: this.$t( 'main.created_by' ) },
        { id: Column.ModifiedDate, name: this.$t( 'main.modified_date' ) },
        { id: Column.ModifiedBy, name: this.$t( 'main.modified_by' ) }
      ];
    },
    searchName() {
      if ( this.searchColumn > Column.UserDefined ) {
        if ( this.type != null ) {
          const attribute = this.type.attributes.find( a => a.id == this.searchColumn - Column.UserDefined );
          if ( attribute != null )
            return attribute.name;
        }
      } else {
        const column = this.systemColumns.find( c => c.id == this.searchColumn );
        if ( column != null )
          return column.name;
      }
      return null;
    },
    searchTitle() {
      if ( this.searchName != null )
        return this.$t( 'main.search_by', [ this.searchName ] );
      else
        return null;
    }
  },
  methods: {
    selectType( type ) {
      this.updateFilters( { type, project: this.project } );
    },
    selectView( view ) {
      this.updateFilters( { type: this.type, view, project: this.project, folder: this.folder } );
    },
    selectProject( project ) {
      this.updateFilters( { type: this.type, view: this.view, project } );
    },
    selectFolder( folder ) {
      this.updateFilters( { type: this.type, view: this.view, project: this.project, folder } );
    },
    updateFilters( { type, view, project, folder } ) {
      if ( view != null ) {
        if ( folder != null )
          this.$router.push( 'list_view_folder', { viewId: view.id, folderId: folder.id } );
        else if ( project != null )
          this.$router.push( 'list_view_project', { viewId: view.id, projectId: project.id } );
        else
          this.$router.push( 'list_view', { viewId: view.id } );
      } else {
        if ( folder != null )
          this.$router.push( 'list_folder', { folderId: folder.id } );
        else if ( project != null )
          this.$router.push( 'list_project', { typeId: type.id, projectId: project.id } );
        else
          this.$router.push( 'list', { typeId: type.id } );
      }
    },
    isSearchColumn( column ) {
      return column.id == this.searchColumn;
    },
    isSearchAttribute( attribute ) {
      if ( this.searchColumn > Column.UserDefined )
        return attribute.id == this.searchColumn - Column.UserDefined;
      else
        return false;
    },
    setSearchColumn( column ) {
      this.$store.commit( 'list/setSearchColumn', { searchColumn: column.id } );
      this.$refs.search.focus();
    },
    setSearchAttribute( attribute ) {
      this.$store.commit( 'list/setSearchColumn', { searchColumn: Column.UserDefined + attribute.id } );
      this.$refs.search.focus();
    },
    search() {
      this.$store.commit( 'list/setSearchText', { searchText: this.$refs.search.value } );
      this.$emit( 'update' );
    },
    reload() {
      this.$emit( 'reload' );
    }
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

#main-toolbar {
  position: absolute;
  left: 0; right: 0;
  top: @header-height; height: @main-toolbar-height;
  padding: 4px 0;
  background-color: @main-toolbar-bg;
  border-bottom: 1px solid @main-toolbar-border-color;

  .type-selected & {
    height: @main-toolbar-3x-height;

    @media ( min-width: @screen-sm-min ) {
      height: @main-toolbar-2x-height;
    }

    @media ( min-width: @screen-lg-min ) {
      height: @main-toolbar-height;
    }
  }

  .row > div {
    margin-top: 4px;
    margin-bottom: 4px;
  }
}

.main-filters {
  white-space: nowrap;

  .dropdown-toggle {
    width: 140px;

    @media ( min-width: @screen-sm-min ) {
      width: 175px;
    }
  }

  .dropdown-menu {
    right: 0;
  }
}

.main-group {
  .group( @margin-top: 0; @margin-bottom: 0 );
}

.main-element {
  .element();
}

.main-element-wide {
  .element-wide();

  > .input-group {
    width: 100%;
  }
}
</style>
