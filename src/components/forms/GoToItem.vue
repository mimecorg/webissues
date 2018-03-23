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
    <FormHeader v-bind:title="$t( 'GoToItem.Title' )" v-on:close="close"/>
    <Prompt path="GoToItem.Prompt"/>
    <FormGroup id="item" v-bind:label="$t( 'GoToItem.ID' )" v-bind:error="error">
      <input ref="item" id="item" type="text" class="form-control" maxlength="11" v-model="value" v-on:keydown.enter="submit">
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="close"/>
  </div>
</template>

<script>
import { ErrorCode } from '@/constants'

export default {
  data() {
    return {
      value: '',
      error: null
    };
  },

  methods: {
    submit() {
      this.error = null;
      let itemId;
      try {
        this.value = this.$parser.normalizeString( this.value, 11 );
        itemId = this.$parser.parseInteger( this.value.replace( /^#/, '' ), 1 );
        this.value = itemId.toString();
      } catch ( error ) {
        if ( error.reason == 'APIError' ) {
          this.error = this.$t( 'ErrorCode.' + error.errorCode );
          this.$refs.item.focus();
          return;
        } else {
          throw error;
        }
      }
      this.$emit( 'block' );
      this.$ajax.post( '/server/api/issues/find.php', { itemId } ).then( issueId => {
        if ( itemId == issueId )
          this.$router.push( 'IssueDetails', { issueId } );
        else
          this.$router.push( 'IssueItem', { issueId, itemId } );
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.ItemNotFound ) {
          this.$emit( 'unblock' );
          this.error = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.item.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    close() {
      this.$emit( 'close' );
    }
  },

  mounted() {
    this.$refs.item.focus();
  }
}
</script>
