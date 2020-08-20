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
  <div class="dropdown-filters">
    <DropdownFilterButton ref="type" fa-class="fa-table" v-bind:text="typeName" v-bind:title="typeTitle" v-bind:filter.sync="typesFilter">
      <li v-bind:class="{ active: type == null }">
        <HyperLink v-on:click="selectType( null )">{{ $t( 'text.SelectType' ) }}</HyperLink>
      </li>
      <template v-if="filteredTypes.length > 0">
        <li role="separator" class="divider"></li>
        <li v-for="t in filteredTypes" v-bind:key="t.id" v-bind:class="{ active: type != null && t.id == type.id }">
          <HyperLink v-on:click="selectType( t )">{{ t.name }}</HyperLink>
        </li>
      </template>
    </DropdownFilterButton>
    <DropdownFilterButton ref="view" fa-class="fa-binoculars" v-bind:text="viewName" v-bind:title="viewTitle" v-bind:filter.sync="viewsFilter">
      <li v-bind:class="{ active: view == null }">
        <HyperLink v-on:click="selectView( null )">{{ $t( 'text.AllIssues' ) }}</HyperLink>
      </li>
      <template v-if="filteredPersonalViews.length > 0">
        <li role="separator" class="divider"></li>
        <li class="dropdown-header">{{ $t( 'title.PersonalViews' ) }}</li>
        <li v-for="v in filteredPersonalViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
          <HyperLink v-on:click="selectView( v )">{{ v.name }}</HyperLink>
        </li>
      </template>
      <template v-if="filteredPublicViews.length > 0">
        <li role="separator" class="divider"></li>
        <li class="dropdown-header">{{ $t( 'title.PublicViews' ) }}</li>
        <li v-for="v in filteredPublicViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
          <HyperLink v-on:click="selectView( v )">{{ v.name }}</HyperLink>
        </li>
      </template>
    </DropdownFilterButton>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import filterItems from '@/utils/filter'

export default {
  props: {
    typeId: Number,
    viewId: Number,
    showPersonal: Boolean
  },

  data() {
    return {
      typesFilter: '',
      viewsFilter: ''
    };
  },

  computed: {
    ...mapState( 'global', [ 'types' ] ),

    availablePublicViews() {
      if ( this.type != null )
        return this.type.views.filter( v => v.public );
      else
        return [];
    },
    availablePersonalViews() {
      if ( this.type != null && this.showPersonal )
        return this.type.views.filter( v => !v.public );
      else
        return [];
    },

    filteredTypes() {
      return filterItems( this.types, this.typesFilter );
    },
    filteredPublicViews() {
      return filterItems( this.availablePublicViews, this.viewsFilter );
    },
    filteredPersonalViews() {
      return filterItems( this.availablePersonalViews, this.viewsFilter );
    },

    type() {
      if ( this.typeId != null )
        return this.types.find( t => t.id == this.typeId );
      else
        return null;
    },
    view() {
      if ( this.viewId != null && this.type != null )
        return this.type.views.find( v => v.id == this.viewId );
      else
        return null;
    },

    typeName() {
      if ( this.type != null )
        return this.type.name;
      else
        return this.$t( 'text.SelectType' );
    },
    typeTitle() {
      if ( this.type != null )
        return this.$t( 'text.Type', [ this.typeName ] );
      else
        return this.$t( 'text.SelectType' );
    },

    viewName() {
      if ( this.view != null )
        return this.view.name;
      else
        return this.$t( 'text.AllIssues' );
    },
    viewTitle() {
      if ( this.view != null )
        return this.$t( 'text.View', [ this.viewName ] );
      else
        return this.$t( 'text.AllIssues' );
    }
  },

  methods: {
    focus() {
      if ( this.type == null )
        this.$refs.type.focus();
      else
        this.$refs.view.focus();
    },

    selectType( type ) {
      if ( type != null )
        this.$emit( 'update:typeId', type.id );
      else
        this.$emit( 'update:typeId', null );
      if ( type == null || type.id != this.typeId )
        this.$emit( 'update:viewId', null );
    },

    selectView( view ) {
      if ( view != null )
        this.$emit( 'update:viewId', view.id );
      else
        this.$emit( 'update:viewId', null );
    }
  }
}
</script>
