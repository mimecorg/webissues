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
  <div id="application-toolbar">
    <div class="container-fluid">
      <div class="row">
        <div class="col-xs-12 col-sm-6 col-lg-4 dropdown-filters">
          <DropdownButton v-if="types.length > 0" fa-class="fa-list" btn-class="btn-primary" v-bind:text="typeName" v-bind:title="typeTitle">
            <div class="dropdown-menu-scroll">
              <li v-for="t in types" v-bind:key="t.id" v-bind:class="{ active: type != null && t.id == type.id }">
                <HyperLink v-on:click="selectType( t )">{{ t.name }}</HyperLink>
              </li>
            </div>
          </DropdownButton>
          <DropdownButton v-if="type != null" fa-class="fa-filter" v-bind:text="viewName" v-bind:title="viewTitle">
            <div class="dropdown-menu-scroll">
              <li v-bind:class="{ active: view == null }">
                <HyperLink v-on:click="selectView( null )">{{ $t( 'text.AllIssues' ) }}</HyperLink>
              </li>
              <template v-if="personalViews.length > 0">
                <li role="separator" class="divider"></li>
                <li class="dropdown-header">{{ $t( 'title.PersonalViews' ) }}</li>
                <li v-for="v in personalViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
                  <HyperLink v-on:click="selectView( v )">{{ v.name }}</HyperLink>
                </li>
              </template>
              <template v-if="publicViews.length > 0">
                <li role="separator" class="divider"></li>
                <li class="dropdown-header">{{ $t( 'title.PublicViews' ) }}</li>
                <li v-for="v in publicViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
                  <HyperLink v-on:click="selectView( v )">{{ v.name }}</HyperLink>
                </li>
              </template>
            </div>
          </DropdownButton>
        </div>
        <div v-if="type != null" class="col-xs-12 col-sm-6 col-lg-4 dropdown-filters">
          <DropdownButton fa-class="fa-object-group" v-bind:text="projectName" v-bind:title="projectTitle">
            <div class="dropdown-menu-scroll">
              <li v-bind:class="{ active: project == null }">
                <HyperLink v-on:click="selectProject( null )">{{ $t( 'text.AllProjects' ) }}</HyperLink>
              </li>
              <template v-if="projects.length > 0">
                <li role="separator" class="divider"></li>
                <li v-for="p in projects" v-bind:key="p.id" v-bind:class="{ active: project != null && p.id == project.id }">
                  <HyperLink v-on:click="selectProject( p )">{{ p.name }}</HyperLink>
                </li>
              </template>
            </div>
          </DropdownButton>
          <DropdownButton fa-class="fa-folder-open-o" v-bind:text="folderName" v-bind:title="folderTitle">
            <div class="dropdown-menu-scroll">
              <li v-bind:class="{ active: folder == null }">
                <HyperLink v-on:click="selectFolder( null )">{{ $t( 'text.AllFolders' ) }}</HyperLink>
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
        <div v-if="type != null" class="col-xs-12 col-lg-4">
          <div class="toolbar-group">
            <div class="toolbar-element toolbar-element-wide">
              <div class="input-group" v-bind:class="{ 'has-error': searchError }">
                <DropdownButton class="input-group-btn" fa-class="fa-chevron-down" v-bind:title="searchTitle">
                  <div class="dropdown-menu-scroll">
                    <li v-for="c in systemColumns" v-bind:class="{ active: isSearchColumn( c ) }">
                      <HyperLink v-on:click="setSearchColumn( c )">{{ c.name }}</HyperLink>
                    </li>
                    <template v-if="type.attributes.length > 0">
                      <li role="separator" class="divider"></li>
                      <li v-for="a in type.attributes" v-bind:class="{ active: isSearchAttribute( a ) }">
                        <HyperLink v-on:click="setSearchAttribute( a )">{{ a.name }}</HyperLink>
                      </li>
                    </template>
                  </div>
                </DropdownButton>
                <input ref="search" type="search" class="form-control" v-bind:placeholder="searchName" v-bind:maxlength="searchLength"
                       v-bind:value="searchText" v-on:input="setSearchText( $event.target.value )" v-on:keydown.enter="search">
                <div class="input-group-btn">
                  <button type="button" class="btn btn-default" v-bind:title="$t( 'cmd.Search' )" v-on:click="search"><span class="fa fa-search" aria-hidden="true"></span></button>
                </div>
              </div>
            </div>
            <div class="toolbar-element">
              <button type="button" class="btn btn-default" v-bind:title="$t( 'cmd.Reload' )" v-on:click="reload"><span class="fa fa-refresh" aria-hidden="true"></span></button>
              <DropdownButton fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
                <li><HyperLink><span class="fa fa-check-circle-o" aria-hidden="true"></span> {{ $t( 'cmd.MarkAllAsRead' ) }}</HyperLink></li>
                <li><HyperLink><span class="fa fa-check-circle" aria-hidden="true"></span> {{ $t( 'cmd.MarkAllAsUnread' ) }}</HyperLink></li>
                <li role="separator" class="divider"></li>
                <li><HyperLink><span class="fa fa-pencil-square-o" aria-hidden="true"></span> {{ $t( 'title.ProjectDescription' ) }}</HyperLink></li>
                <li role="separator" class="divider"></li>
                <li><HyperLink><span class="fa fa-file-text-o" aria-hidden="true"></span> {{ $t( 'cmd.ExportToCSV' ) }}</HyperLink></li>
              </DropdownButton>
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
    ...mapState( 'list', [ 'searchColumn', 'searchText', 'searchValue', 'searchError' ] ),
    ...mapGetters( 'list', [ 'type', 'view', 'publicViews', 'personalViews', 'project', 'folder', 'folders' ] ),

    typeName() {
      if ( this.type != null )
        return this.type.name;
      else
        return this.$t( 'text.SelectType' );
    },
    typeTitle() {
      if ( this.type != null )
        return this.$t( 'text.Type', [ this.type.name ] );
      else
        return this.$t( 'text.SelectType' );
    },
    viewName() {
      if ( this.view != null )
        return this.view.name;
      else
        return this.$t( 'text.AllIssues' );
    },
    viewTitle() {
      if ( this.view != null )
        return this.$t( 'text.View', [ this.viewName ] );
      else
        return this.$t( 'text.AllIssues' );
    },
    projectName() {
      if ( this.project != null )
        return this.project.name;
      else
        return this.$t( 'text.AllProjects' );
    },
    projectTitle() {
      if ( this.project != null )
        return this.$t( 'text.Project', [ this.projectName ] );
      else
        return this.$t( 'text.AllProjects' );
    },
    folderName() {
      if ( this.folder != null )
        return this.folder.name;
      else
        return this.$t( 'text.AllFolders' );
    },
    folderTitle() {
      if ( this.folder != null )
        return this.$t( 'text.Folder', [ this.folderName ] );
      else
        return this.$t( 'text.AllFolders' );
    },

    systemColumns() {
      return [
        { id: Column.ID, name: this.$t( 'title.ID' ) },
        { id: Column.Name, name: this.$t( 'title.Name' ) },
        { id: Column.CreatedDate, name: this.$t( 'title.CreatedDate' ) },
        { id: Column.CreatedBy, name: this.$t( 'title.CreatedBy' ) },
        { id: Column.ModifiedDate, name: this.$t( 'title.ModifiedDate' ) },
        { id: Column.ModifiedBy, name: this.$t( 'title.ModifiedBy' ) }
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
        return this.$t( 'text.SearchBy', [ this.searchName ] );
      else
        return null;
    },

    searchInfo() {
      let attribute = null;

      switch ( this.searchColumn ) {
        case Column.ID:
          attribute = { type: 'NUMERIC' };
          break;
        case Column.Name:
        case Column.Location:
          attribute = { type: 'TEXT' };
          break;
        case Column.CreatedBy:
        case Column.ModifiedBy:
          attribute = { type: 'USER' };
          break;
        case Column.CreatedDate:
        case Column.ModifiedDate:
          attribute = { type: 'DATETIME' };
          break;
        default:
          if ( this.searchColumn > Column.UserDefined ) {
            const id = this.searchColumn - Column.UserDefined;
            attribute = this.type.attributes.find( a => a.id == id );
          }
          break;
      }

      if ( attribute == null )
        return null;

      switch ( attribute.type ) {
        case 'TEXT':
        case 'ENUM':
        case 'USER':
          return { type: 'TEXT' };

        case 'NUMERIC':
          return { type: 'NUMERIC', decimal: attribute.decimal, strip: attribute.strip };

        case 'DATETIME':
          return { type: 'DATETIME' };
      }
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
          this.$router.push( 'ListViewFolder', { viewId: view.id, folderId: folder.id } );
        else if ( project != null )
          this.$router.push( 'ListViewProject', { viewId: view.id, projectId: project.id } );
        else
          this.$router.push( 'ListView', { viewId: view.id } );
      } else {
        if ( folder != null )
          this.$router.push( 'ListFolder', { folderId: folder.id } );
        else if ( project != null )
          this.$router.push( 'ListProject', { typeId: type.id, projectId: project.id } );
        else
          this.$router.push( 'List', { typeId: type.id } );
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

    setSearchText( searchText ) {
      this.$store.commit( 'list/setSearchText', { searchText } );
    },

    search() {
      let searchText;
      let searchValue;
      let searchError = false;
      try {
        searchText = this.$parser.normalizeString( this.searchText, MaxLength.Value, { allowEmpty: true } );
        if ( this.searchColumn == Column.ID ) {
          searchValue = this.$parser.parseInteger( searchText.replace( /^#/, '' ), 1 ).toString();
          searchText = '#' + searchValue;
        } else if ( this.searchInfo != null ) {
          searchText = this.$parser.normalizeAttributeValue( searchText, this.searchInfo );
          searchValue = this.$parser.convertAttributeValue( searchText, this.searchInfo );
        } else {
          searchValue = searchText;
        }
      } catch ( err ) {
        searchError = true;
      }

      if ( !searchError ) {
        this.$store.commit( 'list/setSearchValue', { searchText, searchValue } );
        this.$store.dispatch( 'updateList' );
      } else {
        this.$store.commit( 'list/setSearchError', { searchText } );
      }
    },

    reload() {
      this.$store.dispatch( 'reload' );
    }
  }
}
</script>
