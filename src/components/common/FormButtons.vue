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
  <div class="form-buttons">
    <button v-if="hasOK" class="btn btn-primary" v-on:click="ok">{{ $t( 'Common.OK' ) }}</button>
    <button v-if="hasCancel" class="btn btn-default" v-on:click="cancel">{{ $t( 'Common.Cancel' ) }}</button>
  </div>
</template>

<script>
export default {
  props: {
    hasOK: { type: Boolean, default: true },
    hasCancel: { type: Boolean, default: true }
  },
  methods: {
    ok() {
      this.$emit( 'ok' );
    },
    cancel() {
      this.$emit( 'cancel' );
    }
  }
}
</script>

<style lang="less">
.form-buttons {
  text-align: right;
  margin: 20px 0 15px 0;

  > .btn {
    min-width: 120px;
    margin-left: 10px;
  }
}
</style>
