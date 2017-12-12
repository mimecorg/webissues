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
  <div id="application" v-bind:class="{ 'type-selected': type != null }">
    <Navbar/>
    <MainToolbar v-on:update="updateList" v-on:reload="reload"/>
    <MainGrid v-bind:busy="busy" v-on:update="updateList"/>
    <Window v-if="windowRoute != null" v-bind:route="windowRoute" v-on:error="showError" v-on:close="closeWindow"/>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { ErrorCode } from '@/constants'

import Navbar from '@/components/Navbar'
import MainToolbar from '@/components/MainToolbar'
import MainGrid from '@/components/MainGrid'
import Window from '@/components/Window'

const State = {
  Idle: 0,
  GlobalUpdate: 1,
  ListUpdate: 2
};

export default {
  components: {
    Navbar,
    MainToolbar,
    MainGrid,
    Window
  },
  data() {
    return {
      state: State.Idle,
      mainRoute: null,
      windowRoute: null
    };
  },
  computed: {
    ...mapState( 'global', [ 'baseURL' ] ),
    ...mapGetters( 'global', [ 'isAuthenticated' ] ),
    ...mapGetters( 'global', { checkGlobalUpdate: 'checkUpdate' } ),
    ...mapState( 'list', [ 'searchColumn', 'searchText' ] ),
    ...mapGetters( 'list', [ 'areFiltersEqual', 'hasFilters', 'type' ] ),
    ...mapGetters( 'list', { checkListUpdate: 'checkUpdate' } ),
    busy() {
      return this.state != State.Idle;
    }
  },
  methods: {
    handleHashChange() {
      const route = this.$router.route;
      if ( route == null ) {
        this.showError( this.makeRouteError() );
      } else if ( route.handler == null ) {
        this.mainRoute = route;
        this.windowRoute = null;
        if ( this.areFiltersEqual( route.params ) ) {
          if ( this.state != State.GlobalUpdate ) {
            if ( this.checkGlobalUpdate() )
              this.updateGlobal();
            else if ( this.checkListUpdate() )
              this.updateList();
          }
        } else {
          this.$store.commit( 'list/clear' );
          this.$store.commit( 'list/setFilters', route.params );
          if ( this.state != State.GlobalUpdate ) {
            if ( this.checkGlobalUpdate() )
              this.updateGlobal();
            else if ( this.hasFilters )
              this.updateList();
            else
              this.finishLoading();
          }
        }
      } else {
        if ( this.state == State.Idle )
          this.windowRoute = route;
      }
    },
    updateGlobal() {
      this.state = State.GlobalUpdate;
      this.$store.commit( 'list/cancel' );
      this.$store.dispatch( 'global/load' ).then( () => {
        if ( this.hasFilters )
          this.updateList();
        else
          this.finishLoading();
      } ).catch( error => {
        this.showError( error );
      } );
    },
    updateList() {
      this.state = State.ListUpdate;
      this.$store.commit( 'list/cancel' );
      this.$store.dispatch( 'list/load' ).then( () => {
        this.finishLoading();
      } ).catch( error => {
        this.showError( error );
      } );
    },
    finishLoading() {
      this.state = State.Idle;
      const route = this.$router.route;
      if ( route != null && route.handler != null ) {
        this.$nextTick( () => {
          this.windowRoute = route;
        } );
      }
    },
    closeWindow() {
      if ( this.windowRoute != null && this.windowRoute.name == 'error' ) {
        if ( this.isAuthenticated && this.windowRoute.error.errorCode == ErrorCode.LoginRequired )
          window.location = this.baseURL + '/index.php';
        else
          window.location = this.baseURL + '/client/index.php';
      } else {
        if ( this.mainRoute != null )
          this.$router.push( this.mainRoute.name, this.mainRoute.params );
        else
          this.$router.push( 'home' );
      }
    },
    makeRouteError() {
      const error = new Error( 'No matching route for path: ' + this.$router.path );
      error.reason = 'page_not_found';
      return error;
    },
    showError( error ) {
      this.state = State.Idle;
      this.$store.commit( 'list/clear' );
      this.$nextTick( () => {
        this.windowRoute = { name: 'error', error };
      } );
      console.error( error );
    },
    reload() {
      if ( this.state != State.GlobalUpdate ) {
        if ( this.checkGlobalUpdate() )
          this.updateGlobal();
        else
          this.updateList();
      }
    }
  },
  mounted() {
    window.addEventListener( 'hashchange', this.handleHashChange );
    const route = this.$router.route;
    if ( route == null ) {
      this.showError( this.makeRouteError() );
    } else {
      if ( route.handler == null ) {
        this.mainRoute = route;
        this.$store.commit( 'list/setFilters', route.params );
      }
      this.updateGlobal();
    }
  },
  beforeDestroy() {
    window.removeEventListener( 'hashchange', this.handleHashChange );
    this.$store.commit( 'list/clear' );
  }
}
</script>
