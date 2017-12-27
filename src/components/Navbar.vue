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
          <button v-if="type != null" type="button" class="btn btn-success hidden-xs" v-bind:title="$t( 'Navbar.AddIssue' )">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'Navbar.Add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'Navbar.GoToItem' )">
            <span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'Navbar.GoTo' ) }}</span>
          </button>
          <DropdownButton v-if="isAdministrator || canManageProjects" fa-class="fa-cog" v-bind:text="$t( 'Navbar.Administration' )"
                           text-class="hidden-sm hidden-md" v-bind:title="$t( 'Navbar.AdministrationMenu' )">
            <li><Link><span class="fa fa-object-group" aria-hidden="true"></span> {{ $t( 'Navbar.Projects' ) }}</Link></li>
            <template v-if="isAdministrator">
              <li><Link><span class="fa fa-users" aria-hidden="true"></span> {{ $t( 'Navbar.UserAccounts' ) }}</Link></li>
              <li><Link><span class="fa fa-user-circle-o" aria-hidden="true"></span> {{ $t( 'Navbar.RegistrationRequests' ) }}</Link></li>
              <li><Link><span class="fa fa-list" aria-hidden="true"></span> {{ $t( 'Navbar.IssueTypes' ) }}</Link></li>
            </template>
            <li><Link><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'Navbar.PublicAlerts' ) }}</Link></li>
            <template v-if="isAdministrator">
              <li><Link><span class="fa fa-clock-o" aria-hidden="true"></span> {{ $t( 'Navbar.ArchivedProjects' ) }}</Link></li>
              <li role="separator" class="divider"></li>
              <li><Link><span class="fa fa-wrench" aria-hidden="true"></span> {{ $t( 'Navbar.GeneralSettings' ) }}</Link></li>
              <li><Link><span class="fa fa-lock" aria-hidden="true"></span> {{ $t( 'Navbar.AccessSettings' ) }}</Link></li>
              <li><Link><span class="fa fa-envelope-o" aria-hidden="true"></span> {{ $t( 'Navbar.EmailSettings' ) }}</Link></li>
              <li><Link><span class="fa fa-inbox" aria-hidden="true"></span> {{ $t( 'Navbar.InboxSettings' ) }}</Link></li>
              <li><Link><span class="fa fa-cogs" aria-hidden="true"></span> {{ $t( 'Navbar.AdvancedSettings' ) }}</Link></li>
              <li role="separator" class="divider"></li>
              <li><Link><span class="fa fa-info" aria-hidden="true"></span> {{ $t( 'Navbar.StatusReport' ) }}</Link></li>
              <li><Link><span class="fa fa-book" aria-hidden="true"></span> {{ $t( 'Navbar.EventLog' ) }}</Link></li>
            </template>
          </DropdownButton>
          <DropdownButton fa-class="fa-user" v-bind:text="userName" text-class="hidden-sm hidden-md" v-bind:title="userTitle">
            <template v-if="isAuthenticated">
              <li><Link><span class="fa fa-filter" aria-hidden="true"></span> {{ $t( 'Navbar.PersonalViews' ) }}</Link></li>
              <li><Link><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'Navbar.PersonalAlerts' ) }}</Link></li>
              <li role="separator" class="divider"></li>
              <li><Link><span class="fa fa-sliders" aria-hidden="true"></span> {{ $t( 'Navbar.UserPreferences' ) }}</Link></li>
              <li><Link><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'Navbar.ChangePassword' ) }}</Link></li>
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
              <a type="button" class="btn btn-default" v-bind:title="$t( 'Navbar.WebIssuesManual' )" v-bind:href="baseURL + '/doc/en/index.html'" target="_blank">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </a>
            </div>
          </div>
        </div>
        <div id="navbar-element-toggle" class="navbar-element">
          <button v-if="type != null" type="button" class="btn btn-success" v-bind:title="$t( 'Navbar.AddIssue' )">
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
    }
  },
  methods: {
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

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

#navbar {
  position: absolute;
  left: 0; right: 0;
  top: 0; height: @navbar-height;
  background: @navbar-bg;

  .btn-default {
    .button-variant( @btn-navbar-color, @btn-navbar-bg, @btn-navbar-border );
  }
}

.navbar-group {
  .group();
  margin-top: 8px;
  margin-bottom: 8px;
}

.navbar-element {
  .element();
}

.navbar-element-wide {
  .element-wide();
}

.navbar-brand {
  display: table;
  width: 100%;

  a, a:focus, a:hover {
    text-decoration: none;
    color: @navbar-brand-color;
  }
}

.navbar-brand-logo {
  display: table-cell;
}

.navbar-brand-img {
  .image( '~@/images/webissues-logo.png'; 32px; 32px );
  margin: 1px 10px 1px 0;
}

.navbar-brand-name {
  display: table-cell;
  vertical-align: middle;
  width: 100%;
  max-width: 0;
  color: @navbar-brand-color;
  font-size: @navbar-brand-font-size;
  .ellipsis();
}

.navbar-sub-group, .navbar-sub-element {
  display: inline-block;
  vertical-align: middle;
}

.navbar-version {
  margin-left: 15px;
  margin-right: 15px;
  color: @navbar-text-color;
  .ellipsis();
}

#navbar-element-toggle {
  display: none;

  @media ( max-width: @screen-xs-max ) {
    display: table-cell;
  }
}

#navbar-element-collapse {
  .dropdown-toggle {
    max-width: 200px;
  }

  .dropdown-menu {
    background-color: @navbar-dropdown-bg;
    border-color: @navbar-border-color;

    > li > a {
      color: @navbar-link-color;

      &:hover, &:focus {
        background-color: @navbar-link-active-bg;
        color: @navbar-link-color;
      }
    }

    .divider {
      background-color: @navbar-divider-color;
    }

    .fa {
      color: @navbar-icon-color;
    }
  }

  @media ( max-width: @screen-xs-max ) {
    display: none;
    position: absolute;
    left: 0; right: 0;
    top: @navbar-height;
    max-height: @navbar-collapse-max-height;
    background-color: @navbar-bg;
    padding: 0 15px 0 15px;
    .touch-scroll();
    z-index: 100;

    &.in {
      display: block;
    }

    &.collapsing {
      display: block;
      overflow: hidden;
    }

    > .btn, > .btn-group {
      display: block;
      margin: 5px 0;
    }

    > .btn, > .btn-group, > .btn-group > .btn {
      width: 100%;
      text-align: left;
    }

    .btn, .btn-group, .dropdown-menu {
      float: none;
    }

    .dropdown-menu {
      position: static;
    }

    .dropdown-toggle {
      max-width: initial;
    }

    .dropdown-backdrop {
      display: none;
    }

    .navbar-sub-group {
      display: table;
      margin: 5px 0 8px 0;
    }

    .navbar-sub-element {
      display: table-cell;
      white-space: nowrap;
    }

    .navbar-sub-element-wide {
      .element-wide();
    }

    .navbar-version {
      margin-left: 0;
    }
  }

  @media ( min-width: @screen-sm-min ) {
    display: table-cell!important;
    height: auto!important;
    overflow: visible!important;
  }
}
</style>
