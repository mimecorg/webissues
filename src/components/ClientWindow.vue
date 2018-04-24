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
  <div id="window" v-bind:class="'window-' + size">
    <component v-if="childComponent != null" v-bind:is="childComponent" v-bind="childProps"
               v-on:block="block" v-on:unblock="unblock"/>
    <BusyOverlay v-if="busy"/>
  </div>
</template>

<script>
import forms from '@/components/forms'

export default {
  components: {
    ...forms
  },

  data() {
    return {
      childComponent: 'ClientLogin',
      childProps: {},
      size: 'small',
      busy: false
    };
  },

  methods: {
    block() {
      this.busy = true;
    },
    unblock() {
      this.busy = false;
    }
  }
}
</script>
