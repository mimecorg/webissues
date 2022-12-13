<!--
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
  <BaseForm v-bind:title="$t( 'title.OAuthConfiguration' )" v-bind:breadcrumbs="breadcrumbs" auto-close>
    <FormSection v-bind:title="$t( 'title.AccessToken' )">
      <button v-if="url != null" class="btn btn-default" v-on:click="authenticate">
        <span class="fa fa-sign-in" aria-hidden="true"></span> {{ $t( 'cmd.Authenticate' ) }}
      </button>
    </FormSection>
    <Prompt v-if="url != null"  :path="valid ? 'prompt.AccessTokenValid' : 'prompt.AccessTokenNotValid'" alert-class="alert-default"/>
    <Prompt v-else path="prompt.InvalidOAuthConfiguration" alert-class="alert-warning"/>
  </BaseForm>
</template>

<script>
export default {
  props: {
    authorizationUrl: String,
    isValid: Boolean
  },
  data() {
    return {
      url: this.authorizationUrl,
      valid: this.isValid,
    };
  },
  computed: {
    breadcrumbs() {
      return [
        { label: this.$t( 'title.ServerSettings' ), route: 'ServerSettings' }
      ];
    },
  },
  methods: {
    authenticate() {
      this.authWindow = window.open( this.url );
    },
    refresh() {
      this.$form.block();

      this.$ajax.post( '/settings/oauth/load.php' ).then( ( { authorizationUrl, isValid } ) => {
        this.url = authorizationUrl;
        this.valid = isValid;
        this.$form.unblock();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },
    handleMessage( e ) {
      if ( e.origin == window.origin && e.data.authentication == 'refresh' ) {
        this.refresh();

        if ( this.authWindow != null ) {
          if ( !this.authWindow.closed )
            this.authWindow.close();
          this.authWindow = null;
        }
      }
    }
  },
  mounted() {
    window.addEventListener( 'message', this.handleMessage );
  },
  beforeDestroy() {
    window.removeEventListener( 'message', this.handleMessage );
  }
}
</script>
