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
  <div class="dropdown-filters">
    <DropdownButton ref="type" fa-class="fa-table" v-bind:text="typeName" v-bind:title="typeTitle">
      <div class="dropdown-menu-scroll">
        <li v-bind:class="{ active: type == null }">
          <HyperLink v-on:click="selectType( null )">{{ $t( 'text.SelectType' ) }}</HyperLink>
        </li>
        <template v-if="types.length > 0">
          <li role="separator" class="divider"></li>
          <li v-for="t in types" v-bind:key="t.id" v-bind:class="{ active: type != null && t.id == type.id }">
            <HyperLink v-on:click="selectType( t )">{{ t.name }}</HyperLink>
          </li>
        </template>
      </div>
    </DropdownButton>
    <DropdownButton ref="view" fa-class="fa-binoculars" v-bind:text="viewName" v-bind:title="viewTitle">
      <div class="dropdown-menu-scroll">
        <li v-bind:class="{ active: view == null }">
          <HyperLink v-on:click="selectView( null )">{{ $t( 'text.AllIssues' ) }}</HyperLink>
        </li>
        <template v-if="availablePersonalViews.length > 0">
          <li role="separator" class="divider"></li>
          <li class="dropdown-header">{{ $t( 'title.PersonalViews' ) }}</li>
          <li v-for="v in availablePersonalViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
            <HyperLink v-on:click="selectView( v )">{{ v.name }}</HyperLink>
          </li>
        </template>
        <template v-if="availablePublicViews.length > 0">
          <li role="separator" class="divider"></li>
          <li class="dropdown-header">{{ $t( 'title.PublicViews' ) }}</li>
          <li v-for="v in availablePublicViews" v-bind:key="v.id" v-bind:class="{ active: view != null && v.id == view.id }">
            <HyperLink v-on:click="selectView( v )">{{ v.name }}</HyperLink>
          </li>
        </template>
      </div>
    </DropdownButton>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    typeId: Number,
    viewId: Number,
    showPersonal: Boolean
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
