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
  <BaseForm v-bind:title="$t( 'title.ViewSettings' )" v-bind:breadcrumbs="breadcrumbs" size="large" auto-close save-position>
    <template v-if="isAdministrator">
      <FormSection v-bind:title="$t( 'title.DefaultView' )">
        <button type="button" class="btn btn-default" v-on:click="editDefaultView" v-bind:title="$t( 'cmd.EditDefaultView' )">
          <span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}
        </button>
      </FormSection>
      <div class="panel panel-default">
        <div class="panel-body panel-table">
          <div class="row">
            <div class="col-xs-3 col-sm-2">{{ $t( 'label.Columns' ) }}</div>
            <div class="col-xs-9 col-sm-10">{{ defaultColumns }}</div>
          </div>
          <div class="row">
            <div class="col-xs-3 col-sm-2">{{ $t( 'label.SortBy' ) }}</div>
            <div class="col-xs-9 col-sm-10">{{ defaultSortBy }}</div>
          </div>
        </div>
      </div>
      <FormSection v-bind:title="$t( 'title.PublicViews' )">
        <button type="button" class="btn btn-success" v-on:click="addPublicView" v-bind:title="$t( 'cmd.AddPublicView' )">
          <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
        </button>
      </FormSection>
      <Grid v-if="publicViews.length > 0" v-bind:items="publicViews" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-medium', 'column-xlarge', null ]"
            v-on:row-click="rowClickPublic">
        <template slot-scope="{ item, columnIndex, columnClass }">
          <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
        </template>
      </Grid>
      <div v-else class="alert alert-info">
        {{ $t( 'info.NoPublicViews' ) }}
      </div>
    </template>
    <FormSection v-bind:title="$t( 'title.PersonalViews' )">
      <button type="button" class="btn btn-success" v-on:click="addPersonalView" v-bind:title="$t( 'cmd.AddPersonalView' )">
        <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
      </button>
    </FormSection>
    <Grid v-if="personalViews.length > 0" v-bind:items="personalViews" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-medium', 'column-xlarge', null ]"
          v-on:row-click="rowClickPersonal">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoPersonalViews' ) }}
    </div>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { getColumnName } from '@/utils/columns'

export default {
  props: {
    typeId: Number,
    name: String,
    defaultView: Object,
    publicViews: Array,
    personalViews: Array,
    attributes: Array
  },

  computed: {
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    breadcrumbs() {
      return [
        { label: this.$t( 'title.IssueTypes' ), route: 'ManageTypes' },
        { label: this.name, route: 'TypeDetails', params: { typeId: this.typeId } }
      ];
    },
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Columns' ),
        this.$t( 'title.SortBy' )
      ];
    },
    defaultColumns() {
      return this.defaultView.columns.map( c => this.getColumnName( c ) ).join( ', ' );
    },
    defaultSortBy() {
      return this.getSortBy( this.defaultView.sortColumn, this.defaultView.sortAscending );
    }
  },

  methods: {
    getCellValue( columnIndex, view ) {
      switch ( columnIndex ) {
        case 0:
          return view.name;
        case 1:
          return view.columns.map( c => this.getColumnName( c ) ).join( ', ' );
        case 2:
          return this.getSortBy( view.sortColumn, view.sortAscending );
      }
    },

    getColumnName,

    getSortBy( column, ascending ) {
      return this.getColumnName( column ) + ' (' + ( ascending ? this.$t( 'text.ascending' ) : this.$t( 'text.descending' ) ) + ')';
    },

    editDefaultView() {
      this.$router.push( 'EditDefaultView', { typeId: this.typeId } );
    },
    addPublicView() {
      this.$router.push( 'AddPublicView', { typeId: this.typeId } );
    },
    addPersonalView() {
      this.$router.push( 'AddPersonalView', { typeId: this.typeId } );
    },

    rowClickPublic( rowIndex ) {
      this.$router.push( 'EditView', { typeId: this.typeId, viewId: this.publicViews[ rowIndex ].id } );
    },
    rowClickPersonal( rowIndex ) {
      this.$router.push( 'EditView', { typeId: this.typeId, viewId: this.personalViews[ rowIndex ].id } );
    }
  }
}
</script>
