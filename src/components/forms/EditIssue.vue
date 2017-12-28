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
    <FormHeader v-bind:title="$t( 'EditIssue.Title' )" v-on:close="close"/>
    <Prompt path="EditIssue.Prompt"><strong>{{ name }}</strong></Prompt>
    <FormGroup id="name" v-bind:label="$t( 'EditIssue.Name' )" v-bind:error="error">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="maxLength" v-model="value">
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
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
      value: this.name,
      error: null,
      maxLength: MaxLength.Value
    };
  },
  methods: {
    submit() {
      this.error = null;
      const name = this.value.trim();
      if ( name == '' ) {
        this.error = this.$t( 'ErrorCode.' + ErrorCode.EmptyValue );
        this.$refs.name.focus();
        return;
      }
      if ( name == this.name ) {
        this.close();
        return;
      }
      this.$emit( 'block' );
      this.$ajax.post( '/server/api/issue/edit.php', { issueId: this.issueId, name } ).then( stampId => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.$router.push( 'IssueDetails', { issueId: this.issueId } );
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },
    cancel() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
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
