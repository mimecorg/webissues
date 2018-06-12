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
              <a v-if="isWeb" v-bind:href="baseURL + '/client/index.php'">
                <div class="navbar-brand-img"></div>
              </a>
              <HyperLink v-else v-on:click="home">
                <div class="navbar-brand-img"></div>
              </HyperLink>
            </div>
            <div class="navbar-brand-name">
              <a v-if="isWeb" v-bind:href="baseURL + '/client/index.php'">{{ serverName }}</a>
              <HyperLink v-else v-on:click="home">{{ serverName }}</HyperLink>
            </div>
          </div>
        </div>
        <div id="navbar-element-collapse" v-bind:class="[ 'navbar-element', 'collapse', { 'in' : expanded } ]" v-bind:aria-expanded="expanded ? 'true' : 'false'">
          <button v-if="type != null && isAuthenticated" type="button" class="btn btn-success hidden-xs" v-bind:title="$t( 'ApplicationNavbar.AddIssue' )" v-on:click="addIssue">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.Add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'ApplicationNavbar.GoToItem' )" v-on:click="goToItem">
            <span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'ApplicationNavbar.GoTo' ) }}</span>
          </button>
          <DropdownButton v-if="canManageProjects" fa-class="fa-cog" v-bind:text="$t( 'ApplicationNavbar.Administration' )"
                          text-class="hidden-sm hidden-md" v-bind:title="$t( 'ApplicationNavbar.AdministrationMenu' )">
            <li><HyperLink v-on:click="manageProjects"><span class="fa fa-object-group" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.Projects' ) }}</HyperLink></li>
            <li><HyperLink><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.PublicAlerts' ) }}</HyperLink></li>
            <template v-if="isAdministrator">
              <li><HyperLink><span class="fa fa-users" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.UserAccounts' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-user-circle-o" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.RegistrationRequests' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-list" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.IssueTypes' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-clock-o" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.ArchivedProjects' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><HyperLink><span class="fa fa-wrench" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.GeneralSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-lock" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.AccessSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-envelope-o" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.EmailSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-inbox" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.InboxSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-cogs" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.AdvancedSettings' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><HyperLink><span class="fa fa-info" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.StatusReport' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-book" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.EventLog' ) }}</HyperLink></li>
            </template>
          </DropdownButton>
          <button v-else class="btn btn-default" v-on:click="manageProjects">
            <span class="fa fa-object-group" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'ApplicationNavbar.Projects' ) }}</span>
          </button>
          <DropdownButton fa-class="fa-user" v-bind:text="userName" text-class="hidden-sm hidden-md" v-bind:title="userTitle">
            <template v-if="isAuthenticated">
              <li><HyperLink><span class="fa fa-filter" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.PersonalViews' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.PersonalAlerts' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li><HyperLink><span class="fa fa-sliders" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.UserPreferences' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.ChangePassword' ) }}</HyperLink></li>
              <li role="separator" class="divider"></li>
              <li v-if="isWeb"><a v-bind:href="baseURL + '/index.php'"><span class="fa fa-sign-out" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.LogOut' ) }}</a></li>
              <li v-else><HyperLink v-on:click="restartClient"><span class="fa fa-sign-out" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.LogOut' ) }}</HyperLink></li>
            </template>
            <template v-else-if="isWeb">
              <li><a v-bind:href="baseURL + '/index.php'"><span class="fa fa-sign-in" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.LogIn' ) }}</a></li>
              <li v-if="selfRegister"><a v-bind:href="baseURL + '/users/register.php'"><span class="fa fa-user-plus" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.Register' ) }}</a></li>
            </template>
            <template v-else="isWeb">
              <li><HyperLink v-on:click="restartClient"><span class="fa fa-sign-in" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.LogIn' ) }}</HyperLink></li>
              <li v-if="selfRegister"><HyperLink v-on:click="openRegister"><span class="fa fa-user-plus" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.Register' ) }}</HyperLink></li>
            </template>
          </DropdownButton>
          <div class="navbar-sub-group">
            <div class="navbar-sub-element navbar-sub-element-wide">
              <div class="navbar-version">WebIssues {{ serverVersion }}</div>
            </div>
            <div class="navbar-sub-element">
              <button v-if="!isWeb" type="button" class="btn btn-default" v-bind:title="$t( 'ApplicationNavbar.WebIssuesSettings' )" v-on:click="clientSettings">
                <span class="fa fa-wrench" aria-hidden="true"></span>
              </button>
              <button type="button" class="btn btn-default" v-bind:title="$t( 'ApplicationNavbar.AboutWebIssues' )"><span class="fa fa-info-circle" aria-hidden="true"></span></button>
              <a v-if="isWeb" type="button" class="btn btn-default" v-bind:title="$t( 'ApplicationNavbar.WebIssuesManual' )" v-bind:href="manualURL" target="_blank">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </a>
              <button v-else type="button" class="btn btn-default" v-bind:title="$t( 'ApplicationNavbar.WebIssuesManual' )" v-on:click="openManual">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </button>
            </div>
          </div>
        </div>
        <div id="navbar-element-toggle" class="navbar-element">
          <button v-if="type != null" type="button" class="btn btn-success" v-bind:title="$t( 'ApplicationNavbar.AddIssue' )" v-on:click="addIssue">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'ApplicationNavbar.Add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'ApplicationNavbar.ToggleNavigation' )" v-on:click="toggle">
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
        return this.$t( 'ApplicationNavbar.UserTitle', [ this.userName ] );
      else
        return this.$t( 'ApplicationNavbar.AnonymousUser' );
    },
    manualURL() {
      return 'http://doc.mimec.org/webissues/1.1/en/index.html';
    },
    isWeb() {
      return process.env.TARGET == 'web';
    },
    selfRegister() {
      return this.$store.state.global.settings.selfRegister;
    }
  },

  methods: {
    home() {
      this.$router.push( 'Home' );
    },

    addIssue() {
      this.$router.push( 'AddIssue', { typeId: this.type.id } );
    },
    goToItem() {
      this.$router.push( 'GoToItem' );
    },
    manageProjects() {
      this.$router.push( 'ManageProjects' );
    },

    restartClient() {
      if ( process.env.TARGET == 'electron' )
        this.$client.restartClient();
    },

    clientSettings() {
      if ( process.env.TARGET == 'electron' )
        this.$router.push( 'ClientSettings' );
    },

    openRegister() {
      if ( process.env.TARGET == 'electron' )
        this.$client.openURL( this.baseURL + '/users/register.php' );
    },
    openManual() {
      if ( process.env.TARGET == 'electron' )
        this.$client.openURL( this.manualURL );
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
