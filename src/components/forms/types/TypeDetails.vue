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
  <BaseForm v-bind:title="name" v-bind:breadcrumbs="breadcrumbs" size="large" auto-close save-position>
    <template v-slot:header>
      <button type="button" class="btn btn-default" v-on:click="viewSettings"><span class="fa fa-binoculars" aria-hidden="true"></span> {{ $t( 'title.ViewSettings' ) }}</button>
      <DropdownButton v-if="isAdministrator" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="renameType"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.RenameType' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteType"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.DeleteType' ) }}</HyperLink></li>
      </DropdownButton>
    </template>
    <FormSection v-bind:title="$t( 'title.Attributes' )">
      <button v-if="isAdministrator" type="button" class="btn btn-success" v-on:click="addAttribute"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
      <button v-if="isAdministrator && attributes.length > 1" type="button" class="btn btn-default" v-on:click="reorderAttributes">
        <span class="fa fa-random" aria-hidden="true"></span> {{ $t( 'cmd.ChangeOrder' ) }}
      </button>
    </FormSection>
    <Grid v-if="attributes.length > 0" v-bind:items="attributes" v-bind:columns="columns" v-bind:row-click-disabled="!isAdministrator" v-on:row-click="rowClick">
      <template v-slot:type-cell="{ item: attribute }">
        {{ $t( 'AttributeType.' + attribute.type ) }}
      </template>
      <template v-slot:default-cell="{ item: attribute }">
        {{ $formatter.formatExpression( attribute.default, attribute ) }}
      </template>
      <template v-slot:required-cell="{ item: attribute }">
        {{ attribute.required == 1 ? $t( 'text.Yes' ) : $t( 'text.No' ) }}
      </template>
    </Grid>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoAttributes' ) }}
    </div>
  </BaseForm>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  props: {
    typeId: Number,
    name: String,
    attributes: Array
  },

  computed: {
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    breadcrumbs() {
      return [
        { label: this.$t( 'title.IssueTypes' ), route: 'ManageTypes' }
      ];
    },
    columns() {
      return {
        name: { title: this.$t( 'title.Name' ), class: 'column-large' },
        type: { title: this.$t( 'title.Type' ) },
        default: { title: this.$t( 'title.DefaultValue' ) },
        required: { title: this.$t( 'title.Required' ), class: 'column-small' }
      };
    }
  },

  methods: {
    renameType() {
      this.$router.push( 'RenameType', { typeId: this.typeId } );
    },
    deleteType() {
      this.$router.push( 'DeleteType', { typeId: this.typeId } );
    },
    viewSettings() {
      this.$router.push( 'ViewSettings', { typeId: this.typeId } );
    },

    addAttribute() {
      this.$router.push( 'AddAttribute', { typeId: this.typeId } );
    },
    reorderAttributes() {
      this.$router.push( 'ReorderAttributes', { typeId: this.typeId } );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'EditAttribute', { typeId: this.typeId, attributeId: this.attributes[ rowIndex ].id } );
    }
  }
}
</script>
