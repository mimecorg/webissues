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
    <MainToolbar/>
    <MainGrid/>
    <Window v-if="route != null"/>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import Navbar from '@/components/Navbar'
import MainToolbar from '@/components/MainToolbar'
import MainGrid from '@/components/MainGrid'
import Window from '@/components/Window'

export default {
  components: {
    Navbar,
    MainToolbar,
    MainGrid,
    Window
  },
  computed: {
    ...mapGetters( 'list', [ 'type' ] ),
    ...mapState( 'window', [ 'route' ] )
  },
  mounted() {
    this.$store.dispatch( 'initialize' );
  },
  beforeDestroy() {
    this.$store.dispatch( 'destroy' );
  },
  routeChanged( route ) {
    this.$store.dispatch( 'navigate', route );
  }
}
</script>
