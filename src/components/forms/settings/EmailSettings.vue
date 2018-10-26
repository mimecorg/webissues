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
    <FormHeader v-bind:title="$t( 'title.EmailSettings' )" v-on:close="close"/>
    <FormCheckbox v-bind:label="$t( 'text.EnableSendingEmails' )" v-model="isEnabled"/>
    <FormInput v-if="isEnabled" ref="from" id="from" v-bind:label="$t( 'label.EmailAddress' )" v-bind="$field( 'from' )" v-model="from"/>
    <FormCheckbox v-if="isEnabled" v-bind:label="$t( 'text.UseCustomSMTPServer' )" v-model="customServer"/>
    <Panel v-if="isEnabled && customServer" v-bind:title="$t( 'title.SMTPConfiguration' )">
      <FormInput ref="server" id="server" v-bind:label="$t( 'label.ServerName' )" v-bind="$field( 'server' )" v-model="server"/>
      <FormInput ref="port" id="port" v-bind:label="$t( 'label.PortNumber' )" v-bind="$field( 'port' )" v-model="port"/>
      <FormDropdown ref="encryption" v-bind:label="$t( 'label.EncryptionMode' )" v-bind="$field( 'encryption' )"
                    v-bind:items="encryptionItems" v-bind:item-names="encryptionItemNames" v-model="encryption"/>
      <FormInput ref="user" id="user" v-bind:label="$t( 'label.UserName' )" v-bind="$field( 'user' )" v-model="user"/>
      <FormInput ref="password" id="password" type="password" v-bind:label="$t( 'label.Password' )" v-bind="$field( 'password' )" v-model="password"/>
      <div class="panel-buttons">
        <button class="btn btn-default" v-on:click="test">{{ $t( 'cmd.Test' ) }}</button>
      </div>
      <Prompt v-if="testResult == true" path="prompt.TestMessageSent" alert-class="alert-success"/>
      <Prompt v-else-if="testResult == false" path="prompt.TestMessageFailed" alert-class="alert-danger"/>
    </Panel>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { MaxLength } from '@/constants'

export default {
  props: {
    settings: Object
  },

  fields() {
    const fields = {
      from: {
        value: '',
        type: String,
        required: true,
        maxLength: MaxLength.Value,
        parse: this.checkEmailAddress,
        condition: () => this.isEnabled
      },
      server: {
        value: '',
        type: String,
        required: true,
        maxLength: MaxLength.Value,
        condition: () => this.isEnabled && this.customServer
      },
      port: {
        value: '',
        type: String,
        required: true,
        maxLength: 5,
        parse: this.parsePortNumber,
        condition: () => this.isEnabled && this.customServer
      },
      encryption: {
        value: '',
        type: String
      },
      user: {
        value: '',
        type: String,
        maxLength: MaxLength.Value
      },
      password: {
        value: '',
        type: String,
        maxLength: MaxLength.Value
      }
    };

    if ( this.settings.emailFrom != null )
      fields.from.value = this.settings.emailFrom;
    if ( this.settings.smtpServer != null )
      fields.server.value = this.settings.smtpServer;
    if ( this.settings.smtpPort != null )
      fields.port.value = this.settings.smtpPort.toString();
    if ( this.settings.smtpEncryption != null )
      fields.encryption.value = this.settings.smtpEncryption;
    if ( this.settings.smtpUser != null )
      fields.user.value = this.settings.smtpUser;
    if ( this.settings.smtpPassword != null )
      fields.password.value = this.settings.smtpPassword;

    return fields;
  },

  data() {
    return {
      isEnabled: this.settings.emailEngine != null,
      customServer: this.settings.emailEngine == 'smtp',
      testResult: null
    };
  },

  computed: {
    encryptionItems() {
      return [ '', 'ssl', 'tls' ];
    },
    encryptionItemNames() {
      return [ this.$t( 'text.None' ), this.$t( 'text.SSL' ), this.$t( 'text.TLS' ) ];
    }
  },

  methods: {
    submit() {
      this.testResult = null;

      if ( !this.$fields.validate() )
        return;

      const initialIsEnabled = this.settings.emailEngine != null;
      const initialCustomServer = this.settings.emailEngine == 'smtp';

      if ( !this.$fields.modified() && this.isEnabled == initialIsEnabled && this.customServer == initialCustomServer ) {
        this.returnToDetails();
        return;
      }

      const data = {};

      if ( this.isEnabled ) {
        data.emailEngine = this.customServer ? 'smtp' : 'standard';
        data.emailFrom = this.from;
        if ( this.customServer ) {
          data.smtpServer =  this.server;
          data.smtpPort =  Number( this.port );
          data.smtpEncryption =  this.encryption;
          data.smtpUser =  this.user;
          data.smtpPassword =  this.password;
        }
      } else {
        data.emailEngine = '';
      }

      this.$emit( 'block' );

      this.$ajax.post( '/settings/email/edit.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    test() {
      this.testResult = null;

      if ( !this.$fields.validate() )
        return;

      const data = {
        emailFrom: this.from,
        smtpServer: this.server,
        smtpPort: Number( this.port ),
        smtpEncryption: this.encryption,
        smtpUser: this.user,
        smtpPassword: this.password
      };

      this.$emit( 'block' );

      this.$ajax.post( '/settings/email/test.php', data ).then( result => {
        this.$emit( 'unblock' );
        this.testResult = result;
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    checkEmailAddress( value ) {
      this.$parser.checkEmailAddress( value );
      return value;
    },

    parsePortNumber( value ) {
      const port = this.$parser.parseInteger( value, 1, 65535 );
      return port.toString();
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ServerSettings' );
    },

    close() {
      this.$emit( 'close' );
    }
  },

  mounted() {
    if ( this.$refs.from != null )
      this.$refs.from.focus();
  }
}
</script>