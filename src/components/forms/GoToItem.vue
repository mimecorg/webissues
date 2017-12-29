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
      <input ref="item" id="item" type="text" class="form-control" v-model="value" v-on:keydown.enter="submit">
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="close"/>
  </div>
</template>

<script>
import { ErrorCode, MaxLength } from '@/constants'

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
      let itemId = this.value.trim();
      if ( itemId == '' ) {
        this.error = this.$t( 'ErrorCode.' + ErrorCode.EmptyValue );
        this.$refs.item.focus();
        return;
      }
      if ( itemId.substr( 0, 1 ) == '#' )
        itemId = itemId.substr( 1 );
      if ( !/^-?[0-9]+$/.test( itemId ) ) {
        this.error = this.$t( 'ErrorCode.' + ErrorCode.InvalidFormat );
        this.$refs.item.focus();
        return;
      }
      itemId = Number( itemId );
      if ( itemId < 1 ) {
        this.error = this.$t( 'ErrorCode.' + ErrorCode.NumberTooLittle );
        this.$refs.item.focus();
        return;
      }
      if ( itemId >= 2**31 ) {
        this.error = this.$t( 'ErrorCode.' + ErrorCode.NumberTooGreat );
        this.$refs.item.focus();
        return;
      }
      this.$emit( 'block' );
      this.$ajax.post( '/server/api/issue/finditem.php', { itemId } ).then( issueId => {
        if ( itemId == issueId )
          this.$router.push( 'IssueDetails', { issueId } );
        else
          this.$router.push( 'IssueItem', { issueId, itemId } );
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.ItemNotFound ) {
          this.$emit( 'unblock' );
          this.error = this.$t( 'ErrorCode.' + ErrorCode.ItemNotFound );
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
