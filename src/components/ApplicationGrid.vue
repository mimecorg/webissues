<!--
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
  <Grid id="application-grid" v-bind:items="convertedIssues" v-bind:columns="gridColumns"
        sort-enabled v-bind:sort-column="sortColumnKey" v-bind:sort-ascending="sortAscending"
        footer-visible v-bind:previous-enabled="previousEnabled" v-bind:next-enabled="nextEnabled"
        v-bind:status-text="statusText" v-bind:busy="busy" v-on:sort="sort" v-on:previous="previous" v-on:next="next"
        v-on:row-click="rowClick" raw-mode>
    <template v-slot="{ item: issue, column, columnKey }">
      <td v-bind:key="column.id" v-bind:class="column.class" v-html="getCellValue( columnKey, issue )" v-on:mouseenter="onCellEnter( $event.target )"></td>
    </template>
  </Grid>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Column } from '@/constants'

export default {
  computed: {
    ...mapGetters( [ 'busy' ] ),
    ...mapState( 'global', [ 'settings' ] ),
    ...mapState( 'list', [ 'sortColumn', 'sortAscending', 'columns', 'issues', 'totalCount' ] ),
    ...mapGetters( 'list', [ 'type', 'firstIndex', 'lastIndex' ] ),
    gridColumns() {
      return this.columns.map( column => {
        const result = { title: column.name, id: column.id };
        if ( column.id == Column.ID )
          result.class = 'column-small';
        else if ( column.id == Column.Name )
          result.class = 'column-xlarge';
        else if ( column.id == Column.Location )
          result.class = 'column-medium';
        return result;
      } );
    },
    columnAttributes() {
      return this.columns.map( column => {
        if ( column.id > Column.UserDefined && this.type != null )
          return this.type.attributes.find( a => a.id == column.id - Column.UserDefined );
        else
          return null;
      } );
    },
    convertedIssues() {
      const attributes = this.columnAttributes;
      return this.issues.map( issue => {
        return {
          ...issue,
          cells: issue.cells.map( ( value, index ) => {
            const id = this.columns[ index ].id;
            if ( id == Column.ModifiedDate || id == Column.CreatedDate )
              return this.$formatter.formatStamp( value );
            else if ( value != '' && attributes[ index ] != null )
              return this.$formatter.convertAttributeValue( value, attributes[ index ] );
            else
              return value;
          } )
        };
      } );
    },
    sortColumnKey() {
      return this.columns.findIndex( c => c.id == this.sortColumn );
    },
    previousEnabled() {
      return this.firstIndex > 1;
    },
    nextEnabled() {
      return this.lastIndex < this.totalCount;
    },
    statusText() {
      if ( this.type == null )
        return this.$t( 'text.NoTypeSelected' );
      else if ( this.totalCount == 0 )
        return this.$t( 'text.NoIssues' );
      else if ( this.firstIndex == 1 && this.lastIndex == this.totalCount )
        return this.$t( 'text.IssuesCount', [ this.totalCount ] );
      else
        return this.$t( 'text.IssuesCountOf', [ this.firstIndex, this.lastIndex, this.totalCount ] );
    }
  },

  methods: {
    getCellValue( columnKey, issue ) {
      let value = issue.cells[ columnKey ];
      if ( this.columns[ columnKey ].id == Column.Name ) {
        if ( issue.subscribed && this.settings.subscriptions )
          value = ' <span class="fa fa-envelope-o issue-subscribed" aria-hidden="true"></span>' + value;
        if ( issue.read < issue.stamp )
          value = '<span class="fa fa-circle issue-' + ( issue.read > 0 ? 'modified' : 'unread' ) + '" aria-hidden="true"></span> ' + value;
      }
      return value;
    },

    sort( columnKey ) {
      this.$store.commit( 'list/setSortOrder', {
        sortColumn: this.columns[ columnKey ].id,
        sortAscending: ( columnKey == this.sortColumnKey ) ? !this.sortAscending : true
      } );
      this.$store.dispatch( 'updateList' );
    },

    previous() {
      this.$store.commit( 'list/setPreviousPage' );
      this.$store.dispatch( 'updateList' );
    },
    next() {
      this.$store.commit( 'list/setNextPage' );
      this.$store.dispatch( 'updateList' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'IssueDetails', { issueId: this.issues[ rowIndex ].id } );
    },

    onCellEnter( cell ) {
      if ( cell.offsetWidth < cell.scrollWidth )
        cell.setAttribute( 'title', cell.innerText.trim() );
      else
        cell.removeAttribute( 'title' );
    }
  }
}
</script>
