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
        <table class="grid-header-table">
          <thead>
            <th v-for="( columnName, columnIndex ) in columnNames" v-bind:class="getColumnClass( columnIndex )" v-on:click="sort( columnIndex )">
              {{ columnName }} <span v-if="columnIndex == sortColumn" v-bind:class="sortClass" aria-hidden="true"></span>
            </th>
          </thead>
        </table>
      </div>
    </div>
    <div class="grid-body">
      <div ref="bodyScroll" class="grid-body-scroll" v-on:scroll="updateHeaderScroll">
        <table class="grid-body-table">
          <tbody>
            <tr v-for="( item, rowIndex ) in items" v-on:click="rowClick( rowIndex )">
              <slot v-for="( columnName, columnIndex ) in columnNames" v-bind:item="item" v-bind:column-index="columnIndex"
                    v-bind:row-index="rowIndex" v-bind:column-class="getColumnClass( columnIndex )"/>
            </tr>
          </tbody>
        </table>
      </div>
      <BusyOverlay v-if="busy"/>
    </div>
    <div class="grid-footer">
      <div class="container-fluid">
        <div class="grid-footer-group">
          <div class="grid-footer-element">
            <button type="button" class="btn btn-default btn-sm" v-bind:disabled="!previousEnabled" v-bind:title="$t( 'Common.Previous' )" v-on:click="previous">
              <span class="fa fa-chevron-left" aria-hidden="true"></span>
            </button>
            <button type="button" class="btn btn-default btn-sm" v-bind:disabled="!nextEnabled" v-bind:title="$t( 'Common.Next' )" v-on:click="next">
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
    columnNames: Array,
    columnClasses: Array,
    sortColumn: Number,
    sortAscending: Boolean,
    previousEnabled: Boolean,
    nextEnabled: Boolean,
    statusText: String,
    busy: Boolean
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
    getColumnClass( columnIndex ) {
      return this.columnClasses != null ? this.columnClasses[ columnIndex ] : null
    },
    sort( columnIndex ) {
      this.$emit( 'sort', columnIndex );
    },
    rowClick( rowIndex ) {
      this.$emit( 'row-click', rowIndex );
    },
    previous() {
      this.$emit( 'previous' );
    },
    next() {
      this.$emit( 'next' );
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

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.grid {
  position: relative;
}

.grid-header {
  height: 26px;
  background: @grid-header-bg;
  border-bottom: 1px solid @grid-header-border-color;
}

.grid-header-scroll {
  overflow: hidden;
}

.grid-header-table {
  th {
    text-align: left;
    padding: 5px 10px 3px 10px;
    color: @grid-header-color;
    text-transform: uppercase;
    font-weight: normal;
    font-size: @grid-header-font-size;
    cursor: pointer;
    .ellipsis();

    &:hover {
      color: @grid-header-hover-color;
    }

    .fa {
      position: relative;
      top: -1px;
      margin-left: 3px;
      color: @grid-header-chevron-color;
    }
  }
}

.grid-body {
  position: absolute;
  left: 0; right: 0;
  top: 26px; bottom: 42px;
}

.grid-body-scroll {
  position: absolute;
  left: 0; right: 0;
  top: 0; bottom: 0;
  .touch-scroll();
}

.grid-body-table {
  tr {
    border-bottom: 1px solid @grid-body-border-color;
    cursor: pointer;

    &:hover {
      background-color: @grid-body-hover-color;
    }
  }

  td {
    padding: 10px;
    color: @grid-body-color;
    .ellipsis();
  }
}

.grid-header-table th, .grid-body-table td {
  min-width: @grid-column-width;
  max-width: @grid-column-width;
}

.grid-footer {
  position: absolute;
  left: 0; right: 0;
  height: 42px; bottom: 0;
  background: @grid-header-bg;
  border-top: 1px solid @grid-header-border-color;
}

.grid-footer-group {
  .group();
  margin-top: 5px;
  margin-bottom: 5px;
}

.grid-footer-element {
  .element();
}

.grid-footer-element-wide {
  .element-wide();
}

.grid-footer-status {
  color: @grid-header-color;
  margin-top: 5px;
  margin-bottom: 5px;
  .ellipsis();
}
</style>
