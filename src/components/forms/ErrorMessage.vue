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
    <title-bar v-bind:title="title" v-on:close="close"></title-bar>
    <div class="alert alert-danger">
      <p>{{ message }}</p>
    </div>
    <form-buttons v-bind:has-cancel="false" v-on:ok="close"></form-buttons>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

import { ErrorCode } from '@/constants'

export default {
  props: {
    error: Error
  },
  computed: {
    ...mapGetters( 'global', [ 'isAuthenticated' ] ),
    title() {
      switch ( this.error.reason ) {
        case 'page_not_found':
          return this.$t( 'error.page_not_found' );
        case 'network_error':
          return this.$t( 'error.network_error' );
        case 'api_error':
          if ( this.error.errorCode == ErrorCode.LoginRequired ) {
            if ( this.isAuthenticated )
              return this.$t( 'error.session_expired' );
            else
              return this.$t( 'error.login_required' );
          } else if ( this.error.errorCode == ErrorCode.AccessDenied ) {
            return this.$t( 'error.access_denied' );
          } else {
            return this.$t( 'error.unexpected_error' );
          }
        default:
          return this.$t( 'error.unexpected_error' );
      }
    },
    message() {
      switch ( this.error.reason ) {
        case 'page_not_found':
          return this.$t( 'error_message.page_not_found' );
        case 'network_error':
          return this.$t( 'error_messsage.network_error' );
        case 'api_error':
          if ( this.error.errorCode == ErrorCode.LoginRequired ) {
            if ( this.isAuthenticated )
              return this.$t( 'error_message.session_expired' );
            else
              return this.$t( 'error_message.login_required' );
          } else if ( this.error.errorCode == ErrorCode.AccessDenied ) {
            return this.$t( 'error_message.access_denied' );
          } else if ( this.$te( 'error_code.' + this.error.errorCode ) ) {
            return this.$t( 'error_code.' + this.error.errorCode );
          } else {
            return this.$t( 'error_message.unknown_error' );
          }
        case 'invalid_response':
          return this.$t( 'error_message.invalid_response' );
        case 'server_error':
          if ( this.error.errorCode == 501 || this.error.errorCode == 502 )
            return this.$t( 'error_message.server_not_configured' );
          else
            return this.$t( 'error_message.server_error' );
        case 'bad_request':
          if ( this.error.errorCode == 403 )
            return this.$t( 'error_message.upload_error' );
          else
            return this.$t( 'error_message.bad_request' );
        default:
          return this.$t( 'error_message.unknown_error' );
      }
    }
  },
  methods: {
    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
