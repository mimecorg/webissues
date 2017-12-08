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
  <grid-view id="main-grid" v-bind:items="issues" v-bind:column-names="columnNames" v-bind:column-classes="columnClasses"
             v-bind:sort-column="sortColumnIndex" v-bind:sort-ascending="sortAscending" v-bind:previous-enabled="previousEnabled" v-bind:next-enabled="nextEnabled"
             v-bind:status-text="statusText" v-bind:busy="busy" v-on:sort="sort" v-on:previous="previous" v-on:next="next" v-on:row-click="rowClick">
    <template slot-scope="{ item, columnIndex, columnClass }">
      <td v-if="isNameColumn( columnIndex )" v-bind:class="columnClass">
        <span v-if="item.read < item.stamp" v-bind:class="getUnreadClass( item )" aria-hidden="true"></span> {{ item.name }}
      </td>
      <td v-else-if="isLocationColumn( columnIndex )" v-bind:class="columnClass">{{ item.project }} &mdash; {{ item.folder }}</td>
      <td v-else-if="isUserColumn( columnIndex )" v-bind:class="columnClass" v-html="getUserCellValue( columnIndex, item )"></td>
      <td v-else v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
    </template>
  </grid-view>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Column } from '@/constants'

export default {
  props: {
    busy: Boolean
  },
  computed: {
    ...mapState( 'list', [ 'sortColumn', 'sortAscending', 'columns', 'issues', 'totalCount' ] ),
    ...mapGetters( 'list', [ 'firstIndex', 'lastIndex' ] ),
    columnNames() {
      return this.columns.map( column => column.name );
    },
    columnClasses() {
      return this.columns.map( column => {
        if ( column.id == Column.ID )
          return 'column-id';
        else if ( column.id == Column.Name )
          return 'column-name';
        else if ( column.id == Column.Location )
          return 'column-location';
      } );
    },
    sortColumnIndex() {
      return this.columns.findIndex( c => c.id == this.sortColumn );
    },
    previousEnabled() {
      return this.firstIndex > 1;
    },
    nextEnabled() {
      return this.lastIndex < this.totalCount;
    },
    statusText() {
      if ( this.totalCount == 0 )
        return this.$t( 'main.no_issues' );
      else if ( this.firstIndex == 1 && this.lastIndex == this.totalCount )
        return this.$t( 'main.issues_count', [ this.totalCount ] );
      else
        return this.$t( 'main.issues_count_of', [ this.firstIndex, this.lastIndex, this.totalCount ] );
    }
  },
  methods: {
    isNameColumn( columnIndex ) {
      return this.columns[ columnIndex ].id == Column.Name;
    },
    isLocationColumn( columnIndex ) {
      return this.columns[ columnIndex ].id == Column.Location;
    },
    isUserColumn( columnIndex ) {
      return this.columns[ columnIndex ].id > Column.UserDefined;
    },
    getUnreadClass( issue ) {
      return [ 'fa', 'fa-circle', issue.read > 0 ? 'issue-modified' : 'issue-unread' ];
    },
    getCellValue( columnIndex, issue ) {
      const column = this.columns[ columnIndex ].id;
      switch ( column ) {
        case Column.ID:
          return '#' + issue.id;
        case Column.CreatedDate:
          return issue.createdDate;
        case Column.CreatedBy:
          return issue.createdBy;
        case Column.ModifiedDate:
          return issue.modifiedDate;
        case Column.ModifiedBy:
          return issue.modifiedBy;
      }
    },
    getUserCellValue( columnIndex, issue ) {
      const column = this.columns[ columnIndex ].id;
      return issue[ 'a' + ( column - Column.UserDefined ) ];
    },
    sort( columnIndex ) {
      this.$store.commit( 'list/setSortOrder', {
        sortColumn: this.columns[ columnIndex ].id,
        sortAscending: ( columnIndex == this.sortColumnIndex ) ? !this.sortAscending : true
      } );
      this.$emit( 'update' );
    },
    previous() {
      this.$store.commit( 'list/setPreviousPage' );
      this.$emit( 'update' );
    },
    next() {
      this.$store.commit( 'list/setNextPage' );
      this.$emit( 'update' );
    },
    rowClick( rowIndex ) {
      this.$router.push( 'edit_issue', { issueId: this.issues[ rowIndex ].id } );
    }
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

#main-grid {
  position: absolute;
  left: 0; right: 0;
  top: @header-height + @main-toolbar-height; bottom: 0;

  .type-selected & {
    top: @header-height + @main-toolbar-3x-height;

    @media ( min-width: @screen-sm-min ) {
      top: @header-height + @main-toolbar-2x-height;
    }

    @media ( min-width: @screen-lg-min ) {
      top: @header-height + @main-toolbar-height;
    }
  }

  .grid-header-table th, .grid-body-table td {
    &.column-id {
      min-width: 100px;
      max-width: 100px;
    }

    &.column-name {
      min-width: 400px;
      max-width: 400px;

      @media ( min-width: @screen-lg-min ) {
        min-width: 600px;
        max-width: 600px;
      }
    }

    &.column-location {
      min-width: 300px;
      max-width: 300px;
    }
  }

  .grid-body-table td .fa {
    margin-right: 5px;

    &.issue-unread {
      color: @brand-primary;
    }

    &.issue-modified {
      color: @brand-success;
    }
  }
}
</style>
