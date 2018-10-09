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
    <FormHeader v-bind:title="title" v-on:close="close"/>
    <Prompt v-if="errorPath == null" v-bind:path="promptPath"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else v-bind:path="errorPath" alert-class="alert-danger"/>
    <FormButtons v-bind:cancel-hidden="errorPath != null" v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { ErrorCode, Reason } from '@/constants'

export default {
  props: {
    mode: String,
    typeId: Number,
    viewId: Number,
    name: String
  },

  data() {
    return {
      errorPath: null
    };
  },

  computed: {
    title() {
      if ( this.mode == 'publish' )
        return this.$t( 'cmd.PublishView' );
      else if ( this.mode == 'unpublish' )
        return this.$t( 'cmd.UnpublishView' );
    },
    promptPath() {
      if ( this.mode == 'publish' )
        return 'prompt.PublishView';
      else if ( this.mode == 'unpublish' )
        return 'prompt.UnpublishView';
    }
  },

  methods: {
    submit() {
      if ( this.errorPath != null ) {
        this.returnToDetails();
        return;
      }

      this.$emit( 'block' );

      const data = { viewId: this.viewId };

      this.$ajax.post( '/server/api/types/views/' + this.mode + '.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.ViewAlreadyExists ) {
          this.$emit( 'unblock' );
          this.errorPath = 'ErrorCode.' + error.errorCode;
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ViewSettings', { typeId: this.typeId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
