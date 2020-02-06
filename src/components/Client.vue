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
  <div id="application">
    <ClientNavbar v-bind:server-name="serverName" v-bind:server-version="serverVersion" v-on:client-settings="clientSettings" v-on:about="about"/>
    <ClientWindow v-bind:child-form="childForm" v-bind:child-props="childProps" v-bind:size="size" v-bind:busy="busy"/>
  </div>
</template>

<script>
import ClientNavbar from '@/components/ClientNavbar'
import ClientWindow from '@/components/ClientWindow'

import { makeVersionError } from '@/utils/errors'

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
      childForm: null,
      childProps: null,
      size: 'small',
      busy: false,
      loaded: false
    };
  },

  methods: {
    setSize( size ) {
      this.size = size;
    },

    close() {
      if ( this.childForm == 'ErrorMessage' || !this.loaded )
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
        this.childForm = 'client/ClientLogin';
        this.childProps = {
          anonymousAccess: this.settings.anonymousAccess,
          selfRegister: this.settings.selfRegister,
          resetPassword: this.settings.resetPassword,
          locale: this.settings.locale
        };
      }
    },

    clientSettings() {
      if ( !this.busy ) {
        this.childForm = 'client/ClientSettings';
        this.childProps = {};
      }
    },

    about() {
      if ( !this.busy ) {
        this.childForm = 'about/AboutForm';
        this.childProps = {
          serverVersion: this.serverVersion
        };
      }
    },

    error( error ) {
      this.childForm = 'ErrorMessage';
      this.childProps = { error, isAuthenticated: false };
      this.busy = false;
      console.error( error );
    }
  },

  form() {
    return {
      setSize: this.setSize,
      setAutoClose() {},
      close: this.close,
      block: this.block,
      unblock: this.unblock,
      error: this.error
    };
  },

  mounted() {
    if ( this.$client.settings.baseURL == null ) {
      this.clientSettings();
      return;
    }

    this.serverName = this.$client.settings.serverName;
    this.serverVersion = this.$client.settings.serverVersion;

    this.busy = true;

    this.$ajax.get( '/info.php' ).then( ( { serverName, serverVersion, settings, responseURL } ) => {
      if ( !this.$client.isSupportedVersion( serverVersion ) )
        throw makeVersionError( serverVersion );

      const match = responseURL.match( /\/server\/api\/info\.php$/ );
      if ( match != null ) {
        const baseURL = responseURL.substr( 0, match.index );
        if ( baseURL != this.$client.settings.baseURL ) {
          this.$client.settings.baseURL = baseURL;
          this.$client.settings.serverName = serverName;
          this.$client.settings.serverVersion = serverVersion;
          this.$client.restartClient();
          return;
        }
      }

      this.$client.settings.serverName = serverName;
      this.$client.settings.serverVersion = serverVersion;
      this.$client.saveSettings();

      this.$i18n.setLocale( settings.locale ).then( () => {
        this.serverName = serverName;
        this.serverVersion = serverVersion;
        this.settings = settings;
        this.busy = false;
        this.loaded = true;

        this.clientLogin();
      } );
    } ).catch( error => {
      this.error( error );
    } );
  }
}
</script>
