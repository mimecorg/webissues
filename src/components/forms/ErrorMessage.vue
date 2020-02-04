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
  <BaseForm v-bind:title="title" size="small" with-buttons cancel-hidden v-on:ok="close">
    <div class="alert alert-danger">
      <p>{{ message }}</p>
    </div>
  </BaseForm>
</template>

<script>
import { ErrorCode, Reason } from '@/constants'

export default {
  props: {
    error: Error,
    isAuthenticated: Boolean
  },
  computed: {
    title() {
      switch ( this.error.reason ) {
        case Reason.PageNotFound:
          return this.$t( 'title.PageNotFound' );
        case Reason.NetworkError:
          return this.$t( 'title.NetworkError' );
        case Reason.APIError:
          if ( this.error.errorCode == ErrorCode.LoginRequired ) {
            if ( this.isAuthenticated )
              return this.$t( 'title.SessionExpired' );
            else
              return this.$t( 'title.LoginRequired' );
          } else if ( this.error.errorCode == ErrorCode.AccessDenied ) {
            return this.$t( 'title.AccessDenied' );
          } else if ( this.error.errorCode == ErrorCode.InvalidCsrfToken ) {
            return this.$t( 'title.SessionChanged' );
          } else {
            return this.$t( 'title.UnexpectedError' );
          }
        case Reason.UnsupportedVersion:
          return this.$t( 'title.UnsupportedVersion' );
        default:
          return this.$t( 'title.UnexpectedError' );
      }
    },
    message() {
      switch ( this.error.reason ) {
        case Reason.PageNotFound:
          return this.$t( 'error.PageNotFound' );
        case Reason.NetworkError:
          return this.$t( 'error.NetworkError' );
        case Reason.APIError:
          if ( this.error.errorCode == ErrorCode.LoginRequired ) {
            if ( this.isAuthenticated )
              return this.$t( 'error.SessionExpired' );
            else
              return this.$t( 'error.LoginRequired' );
          } else if ( this.error.errorCode == ErrorCode.AccessDenied ) {
            return this.$t( 'error.AccessDenied' );
          } else if ( this.error.errorCode == ErrorCode.InvalidCsrfToken ) {
            return this.$t( 'error.SessionChanged' );
          } else if ( this.$te( 'ErrorCode.' + this.error.errorCode ) ) {
            return this.$t( 'ErrorCode.' + this.error.errorCode );
          } else {
            return this.$t( 'error.UnknownError' );
          }
        case Reason.InvalidResponse:
          return this.$t( 'error.InvalidResponse' );
        case Reason.ServerError:
          if ( this.error.errorCode == 501 || this.error.errorCode == 502 )
            return this.$t( 'error.ServerNotConfigured' );
          else
            return this.$t( 'error.ServerError' );
        case Reason.BadRequest:
          if ( this.error.errorCode == 403 )
            return this.$t( 'error.UploadError' );
          else
            return this.$t( 'error.BadRequest' );
        case Reason.UnsupportedVersion:
          return this.$t( 'error.UnsupportedVersion' );
        default:
          return this.$t( 'error.UnknownError' );
      }
    }
  },
  methods: {
    close() {
      this.$form.close();
    }
  }
}
</script>
