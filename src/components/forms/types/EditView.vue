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
  <div class="container-fluid">
    <FormHeader v-bind:title="title" v-on:close="close"/>
    <Prompt path="prompt.EditDefaultView"><strong>{{ typeName }}</strong></Prompt>
    <FormSection v-bind:title="$t( 'title.Columns' )">
      <DropdownButton v-if="availableColumns.length > 0" fa-class="fa-plus" menu-class="dropdown-menu-right" v-bind:title="$t( 'cmd.AddColumn' )">
        <li v-for="column in availableColumns" v-bind:key="column"><HyperLink v-on:click="addColumn( column )">{{ getColumnName( column ) }}</HyperLink></li>
      </DropdownButton>
    </FormSection>
    <draggable class="draggable-container" v-bind:options="{ handle: '.draggable-handle' }" v-bind:move="canMoveColumn" v-model="columns">
      <div v-for="column in columns" v-bind:key="column" class="draggable-item">
        <button v-if="!isFixedColumn( column )" class="btn btn-default" v-bind:title="$t( 'cmd.Remove' )" v-on:click="removeColumn( column )">
          <span class="fa fa-remove" aria-hidden="true"></span>
        </button>
        <div v-bind:class="isFixedColumn( column ) ? 'draggable-fixed' : 'draggable-handle'"><span class="fa fa-bars" aria-hidden="true"></span> {{ getColumnName( column ) }}</div>
      </div>
    </draggable>
    <Panel v-bind:title="$t( 'title.SortOrder' )">
      <FormGroup v-bind:label="$t( 'label.Column' )">
        <div class="dropdown-filters">
          <DropdownButton v-bind:text="sortColumnName">
            <div class="dropdown-menu-scroll">
              <li v-for="c in columns" v-bind:key="c" v-bind:class="{ active: c == sortColumn }">
                <HyperLink v-on:click="sortColumn = c">{{ getColumnName( c ) }}</HyperLink>
              </li>
            </div>
          </DropdownButton>
        </div>
      </FormGroup>
      <FormGroup v-bind:label="$t( 'label.Order' )">
        <div class="radio">
          <label><input type="radio" v-model="sortAscending" v-bind:value="true"> {{ $t( 'text.Ascending' ) }}</label>
        </div>
        <div class="radio">
          <label><input type="radio" v-model="sortAscending" v-bind:value="false"> {{ $t( 'text.Descending' ) }}</label>
        </div>
      </FormGroup>
    </Panel>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { Column } from '@/constants'
import { getColumnName } from '@/utils/columns'

export default {
  props: {
    typeId: Number,
    typeName: String,
    initialView: Object
  },

  data() {
    return {
      columns: this.initialView.columns,
      sortColumn: this.initialView.sortColumn,
      sortAscending: this.initialView.sortAscending
    };
  },

  computed: {
    ...mapState( 'global', [ 'types' ] ),
    title() {
      return this.$t( 'title.DefaultView' );
    },
    type() {
      return this.types.find( t => t.id == this.typeId );
    },
    allColumns() {
      let columns = [
        Column.CreatedDate, Column.CreatedBy, Column.ModifiedDate, Column.ModifiedBy
      ];
      if ( this.type != null )
        columns = [ ...columns, ...this.type.attributes.map( a => Column.UserDefined + a.id ) ];
      return columns;
    },
    availableColumns() {
      return this.allColumns.filter( c => !this.columns.includes( c ) );
    },
    sortColumnName() {
      return this.getColumnName( this.sortColumn );
    }
  },

  methods: {
    submit() {
      const columns = this.columns.join( ',' );
      const initialColumns = this.initialView.columns.join( ',' );

      if ( columns == initialColumns && this.sortColumn == this.initialView.sortColumn && this.sortAscending == this.initialView.sortAscending ) {
        this.returnToDetails();
        return;
      }

      const data = { typeId: this.typeId, columns, sortColumn: this.sortColumn, sortAscending: this.sortAscending };

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/types/views/default.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    isFixedColumn( column ) {
      return column == Column.ID || column == Column.Name;
    },

    getColumnName,

    canMoveColumn( e ) {
      return !this.isFixedColumn( e.relatedContext.element );
    },

    addColumn( column ) {
      this.columns = [ ...this.columns, column ];
    },
    removeColumn( column ) {
      this.columns = this.columns.filter( c => c != column );
      if ( this.sortColumn == column )
        this.sortColumn = Column.ID;
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ViewSettings', { typeId: this.typeId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
