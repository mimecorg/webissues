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
    <FormHeader v-bind:title="name" v-bind:breadcrumbs="breadcrumbs" v-on:close="close">
      <button type="button" class="btn btn-default"><span class="fa fa-filter" aria-hidden="true"></span> {{ $t( 'title.ViewSettings' ) }}</button>
      <DropdownButton fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="renameType"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.RenameType' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteType"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.DeleteType' ) }}</HyperLink></li>
      </DropdownButton>
    </FormHeader>
    <FormSection v-bind:title="$t( 'title.Attributes' )">
      <button type="button" class="btn btn-success"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
      <button v-if="attributes.length > 1" type="button" class="btn btn-default"><span class="fa fa-random" aria-hidden="true"></span> {{ $t( 'cmd.Order' ) }}</button>
    </FormSection>
    <Grid v-if="attributes.length > 0" v-bind:items="attributes" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-wide', null, null, null ]">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoAttributes' ) }}
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Access } from '@/constants'
import savePosition from '@/mixins/save-position'

export default {
  mixins: [ savePosition ],

  props: {
    typeId: Number,
    name: String,
    attributes: Array
  },

  computed: {
    ...mapState( 'global', [ 'types', 'users' ] ),
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    breadcrumbs() {
      return [
        { label: this.$t( 'title.IssueTypes' ), route: 'ManageTypes' }
      ];
    },
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Type' ),
        this.$t( 'title.DefaultValue' ),
        this.$t( 'title.Required' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, attribute ) {
      switch ( columnIndex ) {
        case 0:
          return attribute.name;
        case 1:
          return this.$t( 'AttributeType.' + attribute.type );
        case 2:
          return this.$formatter.formatExpression( attribute.default, attribute );
        case 3:
          return attribute.required == 1 ? this.$t( 'text.Yes' ) : this.$t( 'text.No' );
      }
    },

    renameType() {
      this.$router.push( 'RenameType', { typeId: this.typeId } );
    },
    deleteType() {
      this.$router.push( 'DeleteType', { typeId: this.typeId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
