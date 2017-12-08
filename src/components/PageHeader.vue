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
  <div id="header">
    <div class="container-fluid">
      <div class="header-group">
        <div class="header-element header-element-wide">
          <div class="header-brand">
            <div class="header-brand-logo">
              <a v-bind:href="baseURL + '/client/index.php'">
                <div class="header-brand-img"></div>
              </a>
            </div>
            <div class="header-brand-name">
              <a v-bind:href="baseURL + '/client/index.php'">{{ serverName }}</a>
            </div>
          </div>
        </div>
        <div id="header-element-collapse" v-bind:class="[ 'header-element', 'collapse', { 'in' : expanded } ]" v-bind:aria-expanded="expanded ? 'true' : 'false'">
          <button v-if="type != null" type="button" class="btn btn-success hidden-xs" v-bind:title="$t( 'header.add_issue' )">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'header.add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'header.go_to_item' )">
            <span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="hidden-sm hidden-md">{{ $t( 'header.go_to' ) }}</span>
          </button>
          <dropdown-button v-if="isAdministrator || canManageProjects" fa-class="fa-cog" v-bind:text="$t( 'header.administration' )"
                           text-class="hidden-sm hidden-md" v-bind:title="$t( 'header.administration_menu' )">
            <li><action-link><span class="fa fa-object-group" aria-hidden="true"></span> {{ $t( 'header.projects' ) }}</action-link></li>
            <template v-if="isAdministrator">
              <li><action-link><span class="fa fa-user-o" aria-hidden="true"></span> {{ $t( 'header.user_accounts' ) }}</action-link></li>
              <li><action-link><span class="fa fa-user-circle-o" aria-hidden="true"></span> {{ $t( 'header.registration_requests' ) }}</action-link></li>
              <li><action-link><span class="fa fa-list" aria-hidden="true"></span> {{ $t( 'header.issue_types' ) }}</action-link></li>
            </template>
            <li><action-link><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'header.public_alerts' ) }}</action-link></li>
            <template v-if="isAdministrator">
              <li><action-link><span class="fa fa-clock-o" aria-hidden="true"></span> {{ $t( 'header.archived_projects' ) }}</action-link></li>
              <li role="separator" class="divider"></li>
              <li><action-link><span class="fa fa-wrench" aria-hidden="true"></span> {{ $t( 'header.general_settings' ) }}</action-link></li>
              <li><action-link><span class="fa fa-lock" aria-hidden="true"></span> {{ $t( 'header.access_settings' ) }}</action-link></li>
              <li><action-link><span class="fa fa-envelope-o" aria-hidden="true"></span> {{ $t( 'header.email_settings' ) }}</action-link></li>
              <li><action-link><span class="fa fa-inbox" aria-hidden="true"></span> {{ $t( 'header.inbox_settings' ) }}</action-link></li>
              <li><action-link><span class="fa fa-cogs" aria-hidden="true"></span> {{ $t( 'header.advanced_settings' ) }}</action-link></li>
              <li role="separator" class="divider"></li>
              <li><action-link><span class="fa fa-info" aria-hidden="true"></span> {{ $t( 'header.status_report' ) }}</action-link></li>
              <li><action-link><span class="fa fa-book" aria-hidden="true"></span> {{ $t( 'header.event_log' ) }}</action-link></li>
            </template>
          </dropdown-button>
          <dropdown-button fa-class="fa-user" v-bind:text="userName" text-class="hidden-sm hidden-md" v-bind:title="userTitle">
            <li><action-link><span class="fa fa-filter" aria-hidden="true"></span> {{ $t( 'header.personal_views' ) }}</action-link></li>
            <li><action-link><span class="fa fa-bell-o" aria-hidden="true"></span> {{ $t( 'header.personal_alerts' ) }}</action-link></li>
            <li role="separator" class="divider"></li>
            <li><action-link><span class="fa fa-sliders" aria-hidden="true"></span> {{ $t( 'header.user_preferences' ) }}</action-link></li>
            <li><action-link><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'header.change_password' ) }}</action-link></li>
            <li role="separator" class="divider"></li>
            <li><a v-bind:href="baseURL + '/index.php'"><span class="fa fa-sign-out" aria-hidden="true"></span> {{ $t( 'header.log_out' ) }}</a></li>
          </dropdown-button>
          <div class="header-sub-group">
            <div class="header-sub-element header-sub-element-wide">
              <div class="header-version">WebIssues {{ serverVersion }}</div>
            </div>
            <div class="header-sub-element">
              <button type="button" class="btn btn-default" v-bind:title="$t( 'header.about_webissues' )"><span class="fa fa-info-circle" aria-hidden="true"></span></button>
              <a type="button" class="btn btn-default" v-bind:title="$t( 'header.webissues_manual' )" v-bind:href="baseURL + '/doc/en/index.html'" target="_blank">
                <span class="fa fa-question-circle" aria-hidden="true"></span>
              </a>
            </div>
          </div>
        </div>
        <div id="header-element-toggle" class="header-element">
          <button v-if="type != null" type="button" class="btn btn-success" v-bind:title="$t( 'header.add_issue' )">
            <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'header.add' ) }}
          </button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'header.toggle_navigation' )" v-on:click="toggle">
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
    ...mapGetters( 'global', [ 'isAdministrator', 'canManageProjects' ] ),
    ...mapGetters( 'list', [ 'type' ] ),
    userTitle() {
      return this.$t( 'header.user_title', [ this.userName ] );
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

#header {
  position: absolute;
  left: 0; right: 0;
  top: 0; height: @header-height;
  background: @header-bg;

  .btn-default {
    .button-variant( @btn-header-color, @btn-header-bg, @btn-header-border );
  }
}

.header-group {
  .group();
  margin-top: 8px;
  margin-bottom: 8px;
}

.header-element {
  .element();
}

.header-element-wide {
  .element-wide();
}

.header-brand {
  display: table;
  width: 100%;

  a, a:focus, a:hover {
    text-decoration: none;
    color: @header-brand-color;
  }
}

.header-brand-logo {
  display: table-cell;
}

.header-brand-img {
  .image( '~@/images/webissues-logo.png'; 32px; 32px );
  margin: 1px 10px 1px 0;
}

.header-brand-name {
  display: table-cell;
  vertical-align: middle;
  width: 100%;
  max-width: 0;
  color: @header-brand-color;
  font-size: @header-brand-font-size;
  .ellipsis();
}

.header-sub-group, .header-sub-element {
  display: inline-block;
  vertical-align: middle;
}

.header-version {
  margin-left: 15px;
  margin-right: 15px;
  color: @header-text-color;
  .ellipsis();
}

#header-element-toggle {
  display: none;

  @media ( max-width: @screen-xs-max ) {
    display: table-cell;
  }
}

#header-element-collapse {
  .dropdown-toggle {
    max-width: 200px;
  }

  .dropdown-menu {
    background-color: @header-dropdown-bg;
    border-color: @header-border-color;

    > li > a {
      color: @header-link-color;

      &:hover, &:focus {
        background-color: @header-link-active-bg;
        color: @header-link-color;
      }
    }

    .divider {
      background-color: @header-divider-color;
    }

    .fa {
      color: @header-icon-color;
    }
  }

  @media ( max-width: @screen-xs-max ) {
    display: none;
    position: absolute;
    left: 0; right: 0;
    top: @header-height;
    max-height: @navbar-collapse-max-height;
    background-color: @header-bg;
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

    .header-sub-group {
      display: table;
      margin: 5px 0 8px 0;
    }

    .header-sub-element {
      display: table-cell;
      white-space: nowrap;
    }

    .header-sub-element-wide {
      .element-wide();
    }

    .header-version {
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
