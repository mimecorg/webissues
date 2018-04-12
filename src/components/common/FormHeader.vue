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
  <div class="form-header">
    <div class="form-header-group">
      <div class="form-header-title">
        <h1>{{ title }}</h1>
      </div>
      <div class="form-header-buttons">
        <slot/>
        <button type="button" class="btn btn-default" v-bind:title="$t( 'Common.Close' )" v-on:click="close"><span class="fa fa-remove" aria-hidden="true"></span></button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: String
  },
  methods: {
    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
