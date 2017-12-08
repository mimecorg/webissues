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
  <div class="titlebar">
    <div class="titlebar-title">
      <h1>{{ title }}</h1>
    </div>
    <div class="titlebar-buttons">
      <button type="button" class="btn btn-default" v-bind:title="$t( 'common.close' )" v-on:click="close"><span class="fa fa-remove" aria-hidden="true"></span></button>
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

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.titlebar {
  .group();
  margin-top: 15px;
  margin-bottom: 15px;
}

.titlebar-title {
  .element();
  .element-wide();

  > h1 {
    margin: 6px 0;
    font-weight: bold;
    font-size: @titlebar-font-size;
    line-height: @line-height-base;
    white-space: normal;
  }
}

.titlebar-buttons {
  .element();
}
</style>
