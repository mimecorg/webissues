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
          <div class="navbar-title">
            <a v-if="isWeb" v-bind:href="baseURL + '/client/index.php'">{{ serverName }}</a>
            <HyperLink v-else v-on:click="home">{{ serverName }}</HyperLink>
          </div>
        </div>
        <div id="navbar-element-collapse" v-bind:class="[ 'navbar-element', 'collapse', { 'in' : expanded } ]" v-bind:aria-expanded="expanded ? 'true' : 'false'">
          <button v-if="type != null && isAuthenticated" type="button" class="btn btn-success hidden-xs" v-bind:title="$t( 'cmd.AddIssue' )" v-on:click="addIssue">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'cmd.GoToItem' )" v-on:click="goToItem">
            <span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'cmd.GoTo' ) }}</span>
          </button>
          <DropdownButton v-if="isAuthenticated" fa-class="fa-cog" v-bind:text="$t( 'title.Tools' )"
                          text-class="hidden-sm hidden-md" v-bind:title="$t( 'title.Tools' )">
            <li><HyperLink v-on:click="manageProjects"><span class="fa fa-briefcase" aria-hidden="true"></span> {{ $t( 'title.Projects' ) }}</HyperLink></li>
            <li><HyperLink v-on:click="manageTypes"><span class="fa fa-table" aria-hidden="true"></span> {{ $t( 'title.IssueTypes' ) }}</HyperLink></li>
            <li v-if="isAdministrator"><HyperLink v-on:click="manageUsers"><span class="fa fa-users" aria-hidden="true"></span> {{ $t( 'title.UserAccounts' ) }}</HyperLink></li>
            <li role="separator" class="divider"></li>
            <li><HyperLink v-on:click="manageAlerts"><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'title.Alerts' ) }}</HyperLink></li>
            <li v-if="settings.reports"><HyperLink v-on:click="manageReports"><span class="fa fa-calendar-check-o" aria-hidden="true"></span> {{ $t( 'title.Reports' ) }}</HyperLink></li>
            <template v-if="isAdministrator">
              <li role="separator" class="divider"></li>
              <li><HyperLink v-on:click="serverSettings"><span class="fa fa-wrench" aria-hidden="true"></span> {{ $t( 'title.ServerSettings' ) }}</HyperLink></li>
              <li><HyperLink><span class="fa fa-book" aria-hidden="true"></span> {{ $t( 'title.EventLog' ) }}</HyperLink></li>
            </template>
          </DropdownButton>
          <button v-else class="btn btn-default" v-bind:title="$t( 'title.Projects' )" v-on:click="manageProjects">
            <span class="fa fa-briefcase" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'title.Projects' ) }}</span>
          </button>
          <DropdownButton v-if="isAuthenticated" fa-class="fa-user" v-bind:text="userName" text-class="hidden-sm hidden-md" v-bind:title="userTitle">
            <li><HyperLink v-on:click="myAccount"><span class="fa fa-sliders" aria-hidden="true"></span> {{ $t( 'title.MyAccount' ) }}</HyperLink></li>
            <li role="separator" class="divider"></li>
            <li v-if="isWeb"><a v-bind:href="baseURL + '/index.php'"><span class="fa fa-sign-out" aria-hidden="true"></span> {{ $t( 'cmd.LogOut' ) }}</a></li>
            <li v-else><HyperLink v-on:click="restartClient"><span class="fa fa-sign-out" aria-hidden="true"></span> {{ $t( 'cmd.LogOut' ) }}</HyperLink></li>
          </DropdownButton>
          <a v-else-if="isWeb" type="button" class="btn btn-default" v-bind:title="$t( 'cmd.LogIn' )" v-bind:href="baseURL + '/index.php'">
            <span class="fa fa-sign-in" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'cmd.LogIn' ) }}</span>
          </a>
          <button v-else type="button" class="btn btn-default" v-bind:title="$t( 'cmd.LogIn' )" v-on:click="restartClient">
            <span class="fa fa-sign-in" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'cmd.LogIn' ) }}</span>
          </button>
          <AlertsButton v-if="isAuthenticated" class="hidden-xs"/>
          <div class="navbar-sub-group">
            <div class="navbar-sub-element navbar-sub-element-wide">
              <div class="navbar-brand-img"></div>
              <div class="navbar-brand-name">WebIssues {{ serverVersion }}</div>
            </div>
            <div class="navbar-sub-element">
              <button v-if="!isWeb" type="button" class="btn btn-info" v-bind:title="$t( 'title.WebIssuesSettings' )" v-on:click="clientSettings">
                <span class="fa fa-wrench" aria-hidden="true"></span>
              </button>
              <button type="button" class="btn btn-info" v-bind:title="$t( 'title.AboutWebIssues' )"><span class="fa fa-info-circle" aria-hidden="true"></span></button>
              <a v-if="isWeb" type="button" class="btn btn-info" v-bind:title="$t( 'title.WebIssuesManual' )" v-bind:href="manualURL" target="_blank">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </a>
              <button v-else type="button" class="btn btn-info" v-bind:title="$t( 'title.WebIssuesManual' )" v-on:click="openManual">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </button>
            </div>
          </div>
        </div>
        <div id="navbar-element-toggle" class="navbar-element">
          <button v-if="type != null && isAuthenticated" type="button" class="btn btn-success" v-bind:title="$t( 'cmd.AddIssue' )" v-on:click="addIssue">
            <span class="fa fa-plus" aria-hidden="true"></span>
          </button>
          <AlertsButton v-if="isAuthenticated"/>
          <button type="button" class="btn btn-info" v-bind:title="$t( 'cmd.ToggleNavigation' )" v-on:click="toggle">
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
    ...mapState( 'global', [ 'baseURL', 'serverName', 'serverVersion', 'userName', 'settings' ] ),
    ...mapGetters( 'global', [ 'isAuthenticated', 'isAdministrator' ] ),
    ...mapGetters( 'list', [ 'type' ] ),
    userTitle() {
      if ( this.isAuthenticated )
        return this.$t( 'text.User', [ this.userName ] );
      else
        return this.$t( 'text.AnonymousUser' );
    },
    manualURL() {
      return 'http://doc.mimec.org/webissues/1.1/en/index.html';
    },
    isWeb() {
      return process.env.TARGET == 'web';
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
    manageTypes() {
      this.$router.push( 'ManageTypes' );
    },
    manageUsers() {
      this.$router.push( 'ManageUsers' );
    },
    manageAlerts() {
      this.$router.push( 'ManageAlerts' );
    },
    manageReports() {
      this.$router.push( 'ManageReports' );
    },
    serverSettings() {
      this.$router.push( 'ServerSettings' );
    },

    myAccount() {
      this.$router.push( 'MyAccount' );
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
