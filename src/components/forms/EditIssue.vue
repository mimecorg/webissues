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
  <div class="container-fluid">
    <title-bar v-bind:title="$t( 'edit_issue.title' )" v-on:close="close"></title-bar>
    <info-prompt path="edit_issue.prompt"><strong>{{ name }}</strong></info-prompt>
    <error-alert v-bind:error="error"></error-alert>
    <form-group id="name" v-bind:label="$t( 'edit_issue.name' )" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="nameLength" v-model="nameValue">
    </form-group>
    <form-buttons v-on:ok="submit" v-on:cancel="close"></form-buttons>
  </div>
</template>

<script>
import { ErrorCode, MaxLength } from '@/constants'

export default {
  props: {
    issueId: Number,
    name: String
  },
  data() {
    return {
      error: null,
      nameValue: this.name,
      nameError: null,
      nameLength: MaxLength.Value
    };
  },
  methods: {
    submit() {
      this.error = null;
      this.nameError = null;
      const name = this.nameValue.trim();
      if ( name == '' ) {
        this.nameError = this.$t( 'error_code.' + ErrorCode.EmptyValue );
        this.$refs.name.focus();
        return;
      }
      this.$emit( 'block' );
      this.$ajax.post( '/server/api/issue/edit.php', { issueId: this.issueId, name } ).then( ( { updated } ) => {
        if ( updated )
          this.$store.commit( 'list/setDirty' );
        this.close();
      } ).catch( error => {
        this.error = error.message;
        this.$emit( 'unblock' );
        this.$nextTick( () => {
          this.$refs.name.focus();
        } );
      } );
    },
    close() {
      this.$emit( 'close' );
    }
  },
  mounted() {
    this.$refs.name.focus();
  }
}
</script>
