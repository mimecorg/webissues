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
    <Prompt v-bind:path="promptPath"><strong>{{ typeName }}</strong></Prompt>
    <FormInput v-if="mode == 'add'" ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormSection v-bind:title="$t( 'title.Columns' )">
      <DropdownButton v-if="availableColumns.length > 0" fa-class="fa-plus" menu-class="dropdown-menu-right" v-bind:title="$t( 'cmd.AddColumn' )">
        <div class="dropdown-menu-scroll">
          <li v-for="column in availableColumns" v-bind:key="column"><HyperLink v-on:click="addColumn( column )">{{ getColumnName( column ) }}</HyperLink></li>
        </div>
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
    <template v-if="mode == 'add'">
      <FormSection v-bind:title="$t( 'title.Filters' )">
        <DropdownButton fa-class="fa-plus" menu-class="dropdown-menu-right" v-bind:title="$t( 'cmd.AddFilter' )">
          <div class="dropdown-menu-scroll">
            <li v-for="column in allColumns" v-bind:key="column"><HyperLink v-on:click="addFilter( column )">{{ getColumnName( column ) }}</HyperLink></li>
          </div>
        </DropdownButton>
      </FormSection>
      <draggable v-if="filters.length > 0" class="filters" v-bind:options="{ handle: '.filters-name' }" v-model="filters">
        <div v-for="filter in filters" v-bind:key="filter.id" class="row">
          <button class="btn btn-default" v-bind:title="$t( 'cmd.Remove' )" v-on:click="removeFilter( filter )"><span class="fa fa-remove" aria-hidden="true"></span></button>
          <div class="col-xs-10 col-sm-3 filters-name"><span class="fa fa-bars" aria-hidden="true"></span> {{ getColumnName( filter.column ) }}</div>
          <div class="col-xs-12 col-sm-4">
            <DropdownButton v-bind:text="getOperatorName( filter.operator )">
              <div class="dropdown-menu-scroll">
                <li v-for="op in getOperators( filter.column )" v-bind:key="op" v-bind:class="{ active: op == filter.operator }">
                  <HyperLink v-on:click="filter.operator = op">{{ getOperatorName( op ) }}</HyperLink>
                </li>
              </div>
            </DropdownButton>
          </div>
          <div v-bind:class="'col-xs-12 col-sm-4' + ( filter.error != null ? ' form-group has-error' : '' )">
            <ValueEditor v-bind:ref="'filter' + filter.id" v-bind:attribute="getAttributeForEditor( filter.column )" with-expressions v-model="filter.value"/>
            <p v-if="filter.error != null" class="help-block">{{ filter.error }}</p>
          </div>
        </div>
      </draggable>
      <div v-else class="alert alert-info">
        {{ $t( 'info.NoFilters' ) }}
      </div>
    </template>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { Column, MaxLength, ErrorCode, Reason } from '@/constants'
import { getColumnName } from '@/utils/columns'

export default {
  props: {
    mode: String,
    typeId: Number,
    typeName: String,
    initialName: String,
    initialView: Object
  },

  fields() {
    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name,
        condition: this.mode == 'add'
      }
    };
  },

  data() {
    return {
      columns: this.initialView.columns,
      sortColumn: this.initialView.sortColumn,
      sortAscending: this.initialView.sortAscending,
      filters: [],
      nextId: 1
    };
  },

  computed: {
    ...mapState( 'global', [ 'types' ] ),
    title() {
      if ( this.mode == 'default' )
        return this.$t( 'title.DefaultView' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddPublicView' );
    },
    promptPath() {
      if ( this.mode == 'default' )
        return 'prompt.EditDefaultView';
      else if ( this.mode == 'add' )
        return 'prompt.AddPublicView';
    },
    type() {
      return this.types.find( t => t.id == this.typeId );
    },
    allColumns() {
      let columns = [
        Column.ID, Column.Name, Column.CreatedDate, Column.CreatedBy, Column.ModifiedDate, Column.ModifiedBy
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
      let valid = this.$fields.validate();

      if ( this.mode == 'add' )
        valid = this.validateFilters( valid );

      if ( !valid )
        return;

      const columns = this.columns.join( ',' );
      const initialColumns = this.initialView.columns.join( ',' );

      if ( this.mode == 'default' && columns == initialColumns && this.sortColumn == this.initialView.sortColumn && this.sortAscending == this.initialView.sortAscending ) {
        this.returnToDetails();
        return;
      }

      const data = { typeId: this.typeId };
      if ( this.mode == 'add' )
        data.name = this.name;
      data.columns = columns;
      data.sortColumn = this.sortColumn;
      data.sortAscending = this.sortAscending;
      if ( this.mode == 'add' )
        data.filters = this.filters.map( f => ( { column: f.column, operator: f.operator, value: f.value } ) );

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/types/views/' + this.mode + '.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.ViewAlreadyExists ) {
          this.$emit( 'unblock' );
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
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

    getColumnType( column ) {
      switch ( column ) {
        case Column.ID:
          return 'NUMERIC';

        case Column.Name:
          return 'TEXT';

        case Column.CreatedBy:
        case Column.ModifiedBy:
          return 'USER';

        case Column.CreatedDate:
        case Column.ModifiedDate:
          return 'DATETIME';

        default:
          if ( column > Column.UserDefined ) {
            const attribute = this.type.attributes.find( a => a.id == column - Column.UserDefined );
            if ( attribute != null )
              return attribute.type;
          }
      }
    },

    getAttributeForEditor( column ) {
      const type = this.getColumnType( column );

      const result = { type };

      if ( type == 'ENUM' ) {
        const attribute = this.type.attributes.find( a => a.id == column - Column.UserDefined );
        if ( attribute != null )
          result.items = attribute.items;
      }

      return result;
    },

    getAttributeForParser( filter ) {
      const type = this.getColumnType( filter.column );

      let result = { type };

      switch ( type ) {
        case 'TEXT':
        case 'ENUM':
        case 'USER':
          result = { type: 'ENUM', editable: 1 };
          break;

        case 'NUMERIC':
          if ( filter.column > Column.UserDefined ) {
            const attribute = this.type.attributes.find( a => a.id == filter.column - Column.UserDefined );
            if ( attribute != null ) {
              result.decimal = attribute.decimal;
              result.strip = attribute.strip;
            }
          }
          break;
      }

      if ( filter.operator != 'EQ' && filter.operator != 'NEQ' )
        result.required = 1;

      return result;
    },

    getOperators( column ) {
      let operators = [ 'EQ', 'NEQ' ];

      const type = this.getColumnType( column );

      switch ( type ) {
        case 'TEXT':
        case 'ENUM':
        case 'USER':
          operators = [ ...operators, 'BEG', 'CON', 'END', 'IN' ];
          break;

        case 'NUMERIC':
        case 'DATETIME':
          operators = [ ...operators, 'LT', 'LTE', 'GT', 'GTE' ];
          break;
      }

      return operators;
    },
    getOperatorName( operator ) {
      return this.$t( 'Operator.' + operator );
    },

    addFilter( column ) {
      this.filters = [ ...this.filters, { id: this.nextId++, column, operator: 'EQ', value: '', error: null } ];
    },
    removeFilter( filter ) {
      this.filters = this.filters.filter( f => f != filter );
    },

    validateFilters( valid ) {
      for ( let i = 0; i < this.filters.length; i++ ) {
        const filter = this.filters[ i ];

        filter.error = null;

        const attribute = this.getAttributeForParser( filter );

        try {
          filter.value = this.$parser.normalizeString( filter.value, MaxLength.Value, { allowEmpty: true } );
          filter.value = this.$parser.normalizeExpression( filter.value, attribute );
        } catch ( error ) {
          if ( error.reason == Reason.APIError )
            filter.error = this.$t( 'ErrorCode.' + error.errorCode );
          else
            throw error;
        }

        if ( filter.error != null ) {
          if ( valid )
            this.$refs[ 'filter' + filter.id ][ 0 ].focus();
          valid = false;
        }
      }

      return valid;
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
  },

  mounted() {
    if ( this.$refs.name != null )
      this.$refs.name.focus();
  }
}
</script>
