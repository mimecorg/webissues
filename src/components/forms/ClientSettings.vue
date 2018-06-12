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
    <FormHeader v-if="isConfigured" v-bind:title="$t( 'ClientSettings.WebIssuesSettings' )" v-on:close="close"/>
    <div v-else class="form-header">
      <h1>{{ $t( 'ClientSettings.WebIssuesSettings' ) }}</h1>
    </div>
    <FormInput ref="baseURL" id="baseURL" v-bind:label="$t( 'ClientSettings.ServerURL' )" v-bind="$field( 'baseURL' )" v-model="baseURL"/>
    <FormButtons v-bind:cancel-hidden="!isConfigured" v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  fields() {
    return {
      baseURL: {
        value: this.$client.settings.baseURL != null ? this.$client.settings.baseURL + '/' : '',
        type: String,
        required: true,
        maxLength: 250,
        parse: this.parseURL
      }
    }
  },

  computed: {
    isConfigured() {
      return this.$client.settings.baseURL != null;
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.$emit( 'close' );
        return;
      }

      const baseURL = this.baseURL.replace( /\/$/, '' );

      this.$emit( 'block' );

      this.$ajax.withBaseURL( baseURL ).post( '/server/api/info.php' ).then( ( { serverName, serverVersion } ) => {
        if ( !this.$client.isSupportedVersion( serverVersion ) ) {
          const error = new Error( 'Unsupported server version: ' + serverVersion );
          error.reason = 'UnsupportedVersion';
          throw error;
        }

        this.$client.settings.baseURL = baseURL;
        this.$client.settings.serverName = serverName;
        this.$client.settings.serverVersion = serverVersion;

        this.$client.restartClient();
      } ).catch( error => {
        this.$emit( 'unblock' );
        this.baseURLError = this.errorMessage( error );
        this.$nextTick( () => {
          this.$refs.baseURL.focus();
        } );
      } );
    },

    cancel() {
      this.$emit( 'close' );
    },
    close() {
      this.$emit( 'close' );
    },

    parseURL( value ) {
      const matches = /^(https?:\/\/)?[a-z0-9]+([\-\.][a-z0-9]+)*(:[0-9]{1,5})?(\/.*)?$/i.exec( value );
      if ( matches == null )
        throw this.$fields.makeError( this.$t( 'ClientSettings.InvalidURL' ) );
      if ( matches[ 1 ] == null )
        value = 'http://' + value;
      value = value.replace( /\/+(client\/)?(index\.php)?$/, '' );
      return value + '/';
    },

    errorMessage( error ) {
      switch ( error.reason ) {
        case 'NetworkError':
          return this.$t( 'ErrorMessage.NetworkError' );
        case 'APIError':
        case 'InvalidResponse':
          return this.$t( 'ErrorMessage.InvalidResponse' );
        case 'ServerError':
          if ( error.errorCode == 501 || error.errorCode == 502 )
            return this.$t( 'ErrorMessage.ServerNotConfigured' );
          else
            return this.$t( 'ErrorMessage.ServerError' );
        case 'BadRequest':
          return this.$t( 'ErrorMessage.BadRequest' );
        case 'UnsupportedVersion':
          return this.$t( 'ErrorMessage.UnsupportedVersion' );
        default:
          return this.$t( 'ErrorMessage.UnknownError' );
      }
    }
  },

  mounted() {
    this.$refs.baseURL.focus();
  }
}
</script>
