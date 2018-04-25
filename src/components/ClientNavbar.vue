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
  <div id="navbar">
    <div class="container-fluid">
      <div class="navbar-group">
        <div class="navbar-element navbar-element-wide">
          <div class="navbar-brand">
            <div class="navbar-brand-logo">
              <div class="navbar-brand-img"></div>
            </div>
            <div class="navbar-brand-name">
              {{ serverName != null ? serverName : 'WebIssues' }}
            </div>
          </div>
        </div>
        <div id="navbar-element-collapse" v-bind:class="[ 'navbar-element', 'collapse', { 'in' : expanded } ]" v-bind:aria-expanded="expanded ? 'true' : 'false'">
          <div class="navbar-sub-group">
            <div class="navbar-sub-element navbar-sub-element-wide">
              <div v-if="serverVersion != null" class="navbar-version">WebIssues {{ serverVersion }}</div>
            </div>
            <div class="navbar-sub-element">
              <button v-if="serverName != null" type="button" class="btn btn-default" v-bind:title="$t( 'ClientNavbar.WebIssuesSettings' )" v-on:click="clientSettings">
                <span class="fa fa-wrench" aria-hidden="true"></span>
              </button>
              <button v-if="serverName != null" type="button" class="btn btn-default" v-bind:title="$t( 'ClientNavbar.AboutWebIssues' )"><span class="fa fa-info-circle" aria-hidden="true"></span></button>
              <button type="button" class="btn btn-default" v-bind:title="$t( 'ClientNavbar.WebIssuesManual' )" v-on:click="openManual">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </button>
            </div>
          </div>
        </div>
        <div id="navbar-element-toggle" class="navbar-element">
          <button type="button" class="btn btn-default" v-bind:title="$t( 'ClientNavbar.ToggleNavigation' )" v-on:click="toggle">
            <span class="fa fa-bars" aria-hidden="true"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    serverName: String,
    serverVersion: String
  },

  data() {
    return {
      expanded: false
    }
  },

  computed: {
    manualURL() {
      return 'http://doc.mimec.org/webissues/1.1/en/index.html';
    }
  },

  methods: {
    openManual() {
      this.$client.openExternal( this.manualURL );
    },

    clientSettings() {
      this.$emit( 'client-settings' );
    },

    toggle() {
      this.expanded = !this.expanded;
    },

    handleWindowResize() {
      if ( window.innerWidth >= 768 && this.expanded )
        this.expanded = false;
    }
  },

  mounted() {
    window.addEventListener( 'resize', this.handleWindowResize );
  },
  beforeDestroy() {
    window.removeEventListener( 'resize', this.handleWindowResize );
  }
}
</script>
