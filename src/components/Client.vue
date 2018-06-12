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
  <div id="application">
    <ClientNavbar v-bind:server-name="serverName" v-bind:server-version="serverVersion" v-on:client-settings="clientSettings"/>
    <ClientWindow v-bind:child-component="childComponent" v-bind:child-props="childProps" v-bind:size="size" v-bind:busy="busy"
                  v-on:close="close" v-on:block="block" v-on:unblock="unblock" v-on:error="error"/>
  </div>
</template>

<script>
import ClientNavbar from '@/components/ClientNavbar'
import ClientWindow from '@/components/ClientWindow'

export default {
  components: {
    ClientNavbar,
    ClientWindow
  },

  data() {
    return {
      serverName: null,
      serverVersion: null,
      settings: {},
      childComponent: null,
      childProps: null,
      size: 'small',
      busy: false,
      loaded: false
    };
  },

  methods: {
    close() {
      if ( this.childComponent == 'ErrorMessage' || !this.loaded )
        this.$client.restartClient();
      else
        this.clientLogin();
    },

    block() {
      this.busy = true;
    },
    unblock() {
      this.busy = false;
    },

    clientLogin() {
      if ( !this.busy ) {
        this.childComponent = 'ClientLogin';
        this.childProps = { anonymousAccess: this.settings.anonymousAccess, selfRegister: this.settings.selfRegister };
        this.size = 'small';
      }
    },

    clientSettings() {
      if ( !this.busy ) {
        this.childComponent = 'ClientSettings';
        this.childProps = {};
        this.size = 'normal';
      }
    },

    error( error ) {
      this.childComponent = 'ErrorMessage';
      this.childProps = { error, isAuthenticated: false };
      this.size = 'small';
      this.busy = false;
      console.error( error );
    }
  },

  mounted() {
    if ( this.$client.settings.baseURL == null ) {
      this.clientSettings();
      return;
    }

    this.serverName = this.$client.settings.serverName;
    this.serverVersion = this.$client.settings.serverVersion;

    this.busy = true;

    this.$ajax.post( '/server/api/info.php' ).then( ( { serverName, serverVersion, settings } ) => {
      if ( !this.$client.isSupportedVersion( serverVersion ) ) {
        const error = new Error( 'Unsupported server version: ' + serverVersion );
        error.reason = 'UnsupportedVersion';
        throw error;
      }

      this.$client.settings.serverName = serverName;
      this.$client.settings.serverVersion = serverVersion;
      this.$client.saveSettings();

      this.serverName = serverName;
      this.serverVersion = serverVersion;
      this.settings = settings;
      this.busy = false;
      this.loaded = true;

      this.clientLogin();
    } ).catch( error => {
      this.error( error );
    } );
  }
}
</script>
