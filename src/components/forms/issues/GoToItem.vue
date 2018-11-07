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
  <BaseForm v-bind:title="$t( 'cmd.GoToItem' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="cancel">
    <Prompt path="prompt.GoToItem"/>
    <FormInput ref="item" id="item" v-bind:label="$t( 'label.ID' )" v-bind="$field( 'item' )" v-model="item" v-on:keydown.enter="submit"/>
  </BaseForm>
</template>

<script>
import { ErrorCode, Reason } from '@/constants'

export default {
  fields() {
    return {
      item: {
        type: String,
        required: true,
        maxLength: 11,
        parse: this.parse
      }
    };
  },

  data() {
    return {
      itemId: null
    };
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      this.$form.block();

      this.$ajax.post( '/issues/find.php', { itemId: this.itemId } ).then( issueId => {
        if ( this.itemId == issueId )
          this.$router.push( 'IssueDetails', { issueId } );
        else
          this.$router.push( 'IssueItem', { issueId, itemId: this.itemId } );
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.ItemNotFound ) {
          this.$form.unblock();
          this.itemError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.item.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    parse( value ) {
      this.itemId = this.$parser.parseInteger( value.replace( /^#/, '' ), 1 );
      return this.itemId.toString();
    },

    cancel() {
      this.$form.close();
    }
  },

  mounted() {
    this.$refs.item.focus();
  }
}
</script>
