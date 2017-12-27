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
    <div class="alert alert-danger">
      <p>{{ message }}</p>
    </div>
    <FormButtons v-bind:has-cancel="false" v-on:ok="close"/>
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
        case 'PageNotFound':
          return this.$t( 'Error.PageNotFound' );
        case 'NetworkError':
          return this.$t( 'Error.NetworkError' );
        case 'APIError':
          if ( this.error.errorCode == ErrorCode.LoginRequired ) {
            if ( this.isAuthenticated )
              return this.$t( 'Error.SessionExpired' );
            else
              return this.$t( 'Error.LoginRequired' );
          } else if ( this.error.errorCode == ErrorCode.AccessDenied ) {
            return this.$t( 'Error.AccessDenied' );
          } else {
            return this.$t( 'Error.UnexpectedError' );
          }
        default:
          return this.$t( 'Error.UnexpectedError' );
      }
    },
    message() {
      switch ( this.error.reason ) {
        case 'PageNotFound':
          return this.$t( 'ErrorMessage.PageNotFound' );
        case 'NetworkError':
          return this.$t( 'ErrorMessage.NetworkError' );
        case 'APIError':
          if ( this.error.errorCode == ErrorCode.LoginRequired ) {
            if ( this.isAuthenticated )
              return this.$t( 'ErrorMessage.SessionExpired' );
            else
              return this.$t( 'ErrorMessage.LoginRequired' );
          } else if ( this.error.errorCode == ErrorCode.AccessDenied ) {
            return this.$t( 'ErrorMessage.AccessDenied' );
          } else if ( this.$te( 'ErrorCode.' + this.error.errorCode ) ) {
            return this.$t( 'ErrorCode.' + this.error.errorCode );
          } else {
            return this.$t( 'ErrorMessage.UnknownError' );
          }
        case 'InvalidResponse':
          return this.$t( 'ErrorMessage.InvalidResponse' );
        case 'ServerError':
          if ( this.error.errorCode == 501 || this.error.errorCode == 502 )
            return this.$t( 'ErrorMessage.ServerNotConfigured' );
          else
            return this.$t( 'ErrorMessage.ServerError' );
        case 'BadRequest':
          if ( this.error.errorCode == 403 )
            return this.$t( 'ErrorMessage.UploadError' );
          else
            return this.$t( 'ErrorMessage.BadRequest' );
        default:
          return this.$t( 'ErrorMessage.UnknownError' );
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
