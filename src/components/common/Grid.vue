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
  <div class="grid">
    <div class="grid-header" v-bind:style="{ paddingRight: headerPadding + 'px' }">
      <div ref="headerScroll" class="grid-header-scroll">
        <table v-bind:class="[ 'grid-header-table', { 'grid-header-sort': sortEnabled } ]">
          <thead v-if="sortEnabled">
            <th v-for="( column, columnKey ) in columns" v-bind:key="columnKey" v-bind:class="column.class" v-on:click="sort( columnKey )">
              {{ column.title }} <span v-if="columnKey == sortColumn" v-bind:class="sortClass" aria-hidden="true"></span>
            </th>
            <th class="column-fill"></th>
          </thead>
          <thead v-else>
            <th v-for="( column, columnKey ) in columns" v-bind:key="columnKey" v-bind:class="column.class">{{ column.title }}</th>
            <th class="column-fill"></th>
          </thead>
        </table>
      </div>
    </div>
    <div class="grid-body">
      <div ref="bodyScroll" class="grid-body-scroll" v-on:scroll="updateHeaderScroll">
        <table v-bind:class="[ 'grid-body-table', { 'grid-body-hover': !rowClickDisabled } ]">
          <tbody v-if="rawMode">
            <tr v-for="( item, rowIndex ) in items" v-bind:key="item.id" v-on:click="rowClick( rowIndex )">
              <slot v-for="( column, columnKey ) in columns" v-bind:item="item" v-bind:column="column" v-bind:column-key="columnKey" />
              <td class="column-fill"></td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr v-for="( item, rowIndex ) in items" v-bind:key="item.id" v-on:click="rowClick( rowIndex )">
              <td v-for="( column, columnKey ) in columns" v-bind:key="columnKey" v-bind:class="column.class" v-on:mouseenter="onCellEnter( $event.target )">
                <slot v-bind:name="columnKey + '-cell'" v-bind:item="item">{{ item[ columnKey ] }}</slot>
              </td>
              <td class="column-fill"></td>
            </tr>
          </tbody>
        </table>
      </div>
      <BusyOverlay v-if="busy"/>
    </div>
    <div v-if="footerVisible" class="grid-footer">
      <div class="container-fluid">
        <div class="grid-footer-group">
          <div class="grid-footer-element">
            <button type="button" class="btn btn-default btn-sm" v-bind:disabled="!previousEnabled" v-bind:title="$t( 'cmd.Previous' )" v-on:click="previous">
              <span class="fa fa-chevron-left" aria-hidden="true"></span>
            </button>
            <button type="button" class="btn btn-default btn-sm" v-bind:disabled="!nextEnabled" v-bind:title="$t( 'cmd.Next' )" v-on:click="next">
              <span class="fa fa-chevron-right" aria-hidden="true"></span>
            </button>
          </div>
          <div class="grid-footer-element grid-footer-element-wide">
            <div class="grid-footer-status">{{ statusText }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    items: Array,
    columns: [ Object, Array ],
    sortEnabled: Boolean,
    sortColumn: Number,
    sortAscending: Boolean,
    rowClickDisabled: Boolean,
    footerVisible: Boolean,
    previousEnabled: Boolean,
    nextEnabled: Boolean,
    statusText: String,
    busy: Boolean,
    rawMode: Boolean
  },

  data() {
    return {
      headerPadding: 0
    }
  },

  computed: {
    sortClass() {
      return [ 'fa', this.sortAscending ? 'fa-chevron-down' : 'fa-chevron-up' ];
    }
  },

  watch: {
    items() {
      this.$nextTick( () => {
        this.updateHeaderPadding();
        this.$refs.bodyScroll.scrollLeft = 0;
        this.$refs.bodyScroll.scrollTop = 0;
      } );
    }
  },

  methods: {
    updateHeaderPadding() {
      this.headerPadding = this.$refs.bodyScroll.offsetWidth - this.$refs.bodyScroll.clientWidth;
    },
    updateHeaderScroll() {
      this.$refs.headerScroll.scrollLeft = this.$refs.bodyScroll.scrollLeft;
    },

    sort( columnKey ) {
      this.$emit( 'sort', columnKey );
    },
    rowClick( rowIndex ) {
      if ( !this.rowClickDisabled )
        this.$emit( 'row-click', rowIndex );
    },
    previous() {
      this.$emit( 'previous' );
    },
    next() {
      this.$emit( 'next' );
    },

    onCellEnter( cell ) {
      if ( cell.offsetWidth < cell.scrollWidth )
        cell.setAttribute( 'title', cell.innerText.trim() );
      else
        cell.removeAttribute( 'title' );
    }
  },

  mounted() {
    window.addEventListener( 'resize', this.updateHeaderPadding );
    this.$nextTick( () => {
      this.updateHeaderPadding();
    } );
  },
  beforeDestroy() {
    window.removeEventListener( 'resize', this.updateHeaderPadding );
  }
}
</script>
