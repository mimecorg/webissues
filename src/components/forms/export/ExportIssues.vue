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
  <BaseForm v-bind:title="$t( 'cmd.ExportToCSV' )" v-bind:size="size" with-buttons v-bind:cancel-hidden="type == null" v-on:ok="submit" v-on:cancel="cancel">
    <Prompt v-if="type != null" path="prompt.ExportToCSV"/>
    <Prompt v-else alert-class="alert-warning" path="prompt.NoTypeSelected"/>
    <FormGroup v-if="type != null" v-bind:label="$t( 'label.Type' )" required>
      <div class="radio">
        <label><input type="radio" v-model="exportType" v-bind:value="0"> {{ $t( 'text.TableWithVisibleColumns' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="exportType" v-bind:value="1"> {{ $t( 'text.TableWithAllColumns' ) }}</label>
      </div>
    </FormGroup>
    <FormGroup v-if="type != null" v-bind:label="$t( 'label.Delimiter' )" required>
      <div class="radio">
        <label><input type="radio" v-model="delimiter" value=","> {{ $t( 'text.Comma' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="delimiter" value=";"> {{ $t( 'text.Semicolon' ) }}</label>
      </div>
    </FormGroup>
  </BaseForm>
</template>

<script>
import { mapGetters } from 'vuex'

import FileSaver from 'file-saver'

import { Column } from '@/constants'

export default {
  fields() {
    return {
      exportType: {
        value: 0,
        type: Number
      },
      delimiter: {
        value: ',',
        type: String
      }
    };
  },

  computed: {
    ...mapGetters( 'list', [ 'type' ] ),
    size() {
      return this.type != null ? 'normal' : 'small';
    }
  },

  methods: {
    submit() {
      if ( this.type == null ) {
        this.$form.close();
        return;
      }

      if ( !this.$fields.validate() )
        return;

      this.$form.block();

      this.$store.dispatch( 'list/export', { allColumns: this.exportType == 1 } ).then( ( { columns, issues } ) => {
        const data = this.generateCSV( columns, issues );
        const blob = new Blob( data, { type: 'text/csv; charset=UTF-8' } );
        FileSaver.saveAs( blob, 'WebIssues.csv' );
        this.$form.close();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    cancel() {
      this.$form.close();
    },

    generateCSV( columns, issues ) {
      const attributes = columns.map( column => {
        if ( column.id > Column.UserDefined && this.type != null )
          return this.type.attributes.find( a => a.id == column.id - Column.UserDefined );
        else
          return null;
      } );

      const data = [ String.fromCharCode( 0xFEFF ) ];

      data.push( columns.map( column => column.name ).map( this.escapeCSV ).join( this.delimiter ) );
      data.push( '\r\n' );

      for ( const i in issues ) {
        data.push( issues[ i ].cells.map( ( value, index ) => {
          const id = columns[ index ].id;
          if ( id == Column.ModifiedDate || id == Column.CreatedDate )
            return this.$formatter.formatStamp( value );
          else if ( value != '' && attributes[ index ] != null )
            return this.$formatter.convertAttributeValue( value, attributes[ index ] );
          else
            return value;
        } ).map( this.escapeCSV ).join( this.delimiter ) );
        data.push( '\r\n' );
      }

      return data;
    },

    escapeCSV( value ) {
      if ( value == '' )
        return value;
      else if ( value.startsWith( ' ' ) || value.endsWith( ' ' ) || value.includes( '"' ) || value.includes( this.delimiter ) || value.includes( '\n' ) || value == 'ID' )
        return '"' + value.replace( /"/g, '""' ) + '"';
      else
        return value;
    }
  }
}
</script>
