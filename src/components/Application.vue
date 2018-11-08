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
    <ApplicationNavbar/>
    <ApplicationToolbar/>
    <ApplicationGrid/>
    <ApplicationWindow v-if="route != null"/>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import ApplicationNavbar from '@/components/ApplicationNavbar'
import ApplicationToolbar from '@/components/ApplicationToolbar'
import ApplicationGrid from '@/components/ApplicationGrid'
import ApplicationWindow from '@/components/ApplicationWindow'

export default {
  components: {
    ApplicationNavbar,
    ApplicationToolbar,
    ApplicationGrid,
    ApplicationWindow
  },
  computed: {
    ...mapState( 'global', [ 'serverName' ] ),
    ...mapGetters( 'list', [ 'type', 'title' ] ),
    ...mapState( 'window', [ 'route' ] )
  },
  watch: {
    title( value ) {
      this.updateTitle();
    }
  },
  methods: {
    updateTitle() {
      if ( process.env.TARGET == 'web' && this.route == null ) {
        if ( this.title != null )
          document.title = this.title + ' | ' + this.serverName;
        else
          document.title = this.serverName;
      }
    }
  },
  mounted() {
    this.$store.dispatch( 'initialize' );
    this.updateTitle();
  },
  beforeDestroy() {
    this.$store.dispatch( 'destroy' );
  },
  routeChanged( route ) {
    this.$store.dispatch( 'navigate', route );
    this.updateTitle();
  }
}
</script>
