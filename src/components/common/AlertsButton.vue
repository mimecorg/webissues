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
  <DropdownScrollButton menu-class="dropdown-menu-right" menu-scroll-class="dropdown-menu-scroll-wide" v-bind:title="$t( 'title.Notifications' )" v-bind:has-scroll="hasAlerts">
    <template v-slot:button>
      <span class="fa-stack">
        <span class="fa fa-bell fa-stack-1x" aria-hidden="true"></span>
        <span v-if="hasAlerts" class="fa fa-circle fa-stack-1x fa-stack-top-right" aria-hidden="true"></span>
      </span>
    </template>
    <li v-for="a in personalAlerts" v-bind:key="a.id" >
      <HyperLink v-on:click="openView( a )">
        <span v-html="a.view"></span> <span class="badge">{{ a.count }}</span>
        <div v-if="a.location != null" class="dropdown-subtitle" v-html="a.location"></div>
      </HyperLink>
    </li>
    <li v-if="personalAlerts.length > 0 && publicAlerts.length > 0" role="separator" class="divider"></li>
    <li v-for="a in publicAlerts" v-bind:key="a.id" >
      <HyperLink v-on:click="openView( a )">
        <span v-html="a.view"></span> <span class="badge">{{ a.count }}</span>
        <div v-if="a.location != null" class="dropdown-subtitle" v-html="a.location"></div>
      </HyperLink>
    </li>
    <template v-slot:no-scroll>
      <li><div class="dropdown-info">There are no new notifications.</div></li>
    </template>
  </DropdownScrollButton>
</template>

<script>
import { mapState } from 'vuex'

export default {
  computed: {
    ...mapState( 'alerts', [ 'personalAlerts', 'publicAlerts' ] ),
    hasAlerts() {
      return this.personalAlerts.length > 0 || this.publicAlerts.length > 0;
    }
  },
  methods: {
    openView( alert ) {
      if ( alert.viewId != null ) {
        if ( alert.folderId != null )
          this.pushRoute( 'ListViewFolder', { viewId: alert.viewId, folderId: alert.folderId } );
        else if ( alert.projectId != null )
          this.pushRoute( 'ListViewProject', { viewId: alert.viewId, projectId: alert.projectId } );
        else
          this.pushRoute( 'ListView', { viewId: alert.viewId } );
      } else {
        if ( alert.folderId != null )
          this.pushRoute( 'ListFolder', { folderId: alert.folderId } );
        else if ( alert.projectId != null )
          this.pushRoute( 'ListProject', { typeId: alert.typeId, projectId: alert.projectId } );
        else
          this.pushRoute( 'List', { typeId: alert.typeId } );
      }
    },
    pushRoute( name, params ) {
      const route = this.$router.route;
      if ( route != null && route.name == name && route.params.typeId == params.typeId && route.params.viewId == params.viewId
           && route.params.projectId == params.projectId && route.params.folderId == params.folderId ) {
        this.$store.dispatch( 'reload' );
      } else {
        this.$router.push( name, params );
      }
    }
  }
}
</script>
