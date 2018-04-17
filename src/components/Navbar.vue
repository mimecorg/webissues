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
  <div id="navbar" class="navbar-fixed">
    <div class="container-fluid">
      <div class="navbar-group">
        <div class="navbar-element navbar-element-wide">
          <div class="navbar-brand">
            <div class="navbar-brand-logo">
              <a v-bind:href="baseURL + '/client/index.php'">
                <div class="navbar-brand-img"></div>
              </a>
            </div>
            <div class="navbar-brand-name">
              <a v-bind:href="baseURL + '/client/index.php'">{{ serverName }}</a>
            </div>
          </div>
        </div>
        <div id="navbar-element-collapse" v-bind:class="[ 'navbar-element', 'collapse', { 'in' : expanded } ]" v-bind:aria-expanded="expanded ? 'true' : 'false'">
          <button v-if="type != null && isAuthenticated" type="button" class="btn btn-success hidden-xs" v-bind:title="$t( 'Navbar.AddIssue' )" v-on:click="addIssue">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'Navbar.Add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'Navbar.GoToItem' )" v-on:click="goToItem">
            <span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'Navbar.GoTo' ) }}</span>
          </button>
          <DropdownButton v-if="canManageProjects" fa-class="fa-cog" v-bind:text="$t( 'Navbar.Administration' )"
                          text-class="hidden-sm hidden-md" v-bind:title="$t( 'Navbar.AdministrationMenu' )">
            <li><HyperLink v-on:click="manageProjects"><span class="fa fa-object-group" aria-hidden="true"></span> {{ $t( 'Navbar.Projects' ) }}</HyperLink></li>
            <li><HyperLink><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'Navbar.PublicAlerts' ) }}</HyperLink></li>
            <template v-if="isAdministrator">
              <li><HyperLink><span class="fa fa-users" aria-hidden="true"></span> {{ $t( 'Navbar.UserAccounts' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-user-circle-o" aria-hidden="true"></span> {{ $t( 'Navbar.RegistrationRequests' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-list" aria-hidden="true"></span> {{ $t( 'Navbar.IssueTypes' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-clock-o" aria-hidden="true"></span> {{ $t( 'Navbar.ArchivedProjects' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><HyperLink><span class="fa fa-wrench" aria-hidden="true"></span> {{ $t( 'Navbar.GeneralSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-lock" aria-hidden="true"></span> {{ $t( 'Navbar.AccessSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-envelope-o" aria-hidden="true"></span> {{ $t( 'Navbar.EmailSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-inbox" aria-hidden="true"></span> {{ $t( 'Navbar.InboxSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-cogs" aria-hidden="true"></span> {{ $t( 'Navbar.AdvancedSettings' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><HyperLink><span class="fa fa-info" aria-hidden="true"></span> {{ $t( 'Navbar.StatusReport' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-book" aria-hidden="true"></span> {{ $t( 'Navbar.EventLog' ) }}</HyperLink></li>
            </template>
          </DropdownButton>
          <button v-else class="btn btn-default" v-on:click="manageProjects">
            <span class="fa fa-object-group" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'Navbar.Projects' ) }}</span>
          </button>
          <DropdownButton fa-class="fa-user" v-bind:text="userName" text-class="hidden-sm hidden-md" v-bind:title="userTitle">
            <template v-if="isAuthenticated">
              <li><HyperLink><span class="fa fa-filter" aria-hidden="true"></span> {{ $t( 'Navbar.PersonalViews' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'Navbar.PersonalAlerts' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><HyperLink><span class="fa fa-sliders" aria-hidden="true"></span> {{ $t( 'Navbar.UserPreferences' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'Navbar.ChangePassword' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><a v-bind:href="baseURL + '/index.php'"><span class="fa fa-sign-out" aria-hidden="true"></span> {{ $t( 'Navbar.LogOut' ) }}</a></li>
            </template>
            <template v-else>
              <li><a v-bind:href="baseURL + '/index.php'"><span class="fa fa-sign-in" aria-hidden="true"></span> {{ $t( 'Navbar.LogIn' ) }}</a></li>
              <li><a v-bind:href="baseURL + '/register.php'"><span class="fa fa-user-plus" aria-hidden="true"></span> {{ $t( 'Navbar.Register' ) }}</a></li>
            </template>
          </DropdownButton>
          <div class="navbar-sub-group">
            <div class="navbar-sub-element navbar-sub-element-wide">
              <div class="navbar-version">WebIssues {{ serverVersion }}</div>
            </div>
            <div class="navbar-sub-element">
              <button type="button" class="btn btn-default" v-bind:title="$t( 'Navbar.AboutWebIssues' )"><span class="fa fa-info-circle" aria-hidden="true"></span></button>
              <a type="button" class="btn btn-default" v-bind:title="$t( 'Navbar.WebIssuesManual' )" v-bind:href="manualURL" target="_blank">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </a>
            </div>
          </div>
        </div>
        <div id="navbar-element-toggle" class="navbar-element">
          <button v-if="type != null" type="button" class="btn btn-success" v-bind:title="$t( 'Navbar.AddIssue' )" v-on:click="addIssue">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'Navbar.Add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'Navbar.ToggleNavigation' )" v-on:click="toggle">
            <span class="fa fa-bars" aria-hidden="true"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
  data() {
    return {
      expanded: false
    }
  },

  computed: {
    ...mapState( 'global', [ 'baseURL', 'serverName', 'serverVersion', 'userName' ] ),
    ...mapGetters( 'global', [ 'isAuthenticated', 'isAdministrator', 'canManageProjects' ] ),
    ...mapGetters( 'list', [ 'type' ] ),
    userTitle() {
      if ( this.isAuthenticated )
        return this.$t( 'Navbar.UserTitle', [ this.userName ] );
      else
        return this.$t( 'Navbar.AnonymousUser' );
    },
    manualURL() {
      return 'http://doc.mimec.org/webissues/1.1/en/index.html';
    }
  },

  methods: {
    addIssue() {
      this.$router.push( 'AddIssue', { typeId: this.type.id } );
    },
    goToItem() {
      this.$router.push( 'GoToItem' );
    },
    manageProjects() {
      this.$router.push( 'ManageProjects' );
    },

    toggle() {
      this.expanded = !this.expanded;
    },

    handleWindowResize() {
      if ( window.innerWidth >= 768 && this.expanded )
        this.expanded = false;
    },

    handleHashChange() {
      this.expanded = false;
    }
  },

  mounted() {
    window.addEventListener( 'resize', this.handleWindowResize );
    window.addEventListener( 'hashchange', this.handleHashChange );
  },
  beforeDestroy() {
    window.removeEventListener( 'resize', this.handleWindowResize );
    window.removeEventListener( 'hashchange', this.handleHashChange );
  }
}
</script>
