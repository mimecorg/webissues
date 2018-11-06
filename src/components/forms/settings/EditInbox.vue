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
    <FormHeader v-bind:title="title" v-on:close="close">
      <button v-if="mode == 'edit'" type="button" class="btn btn-default" v-on:click="deleteInbox">
        <span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}
      </button>
    </FormHeader>
    <Prompt v-if="mode == 'edit'" path="prompt.EditEmailInbox"><strong>{{ initialEmail }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddEmailInbox"/>
    <FormGroup v-bind:label="$t( 'label.ServerType' )">
      <div class="radio">
        <label><input type="radio" v-model="engine" value="imap"> {{ $t( 'text.IMAP' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="engine" value="pop3"> {{ $t( 'text.POP3' ) }}</label>
      </div>
    </FormGroup>
    <FormInput ref="email" id="email" v-bind:label="$t( 'label.EmailAddress' )" v-bind="$field( 'email' )" v-model="email"/>
    <Panel v-bind:title="$t( 'title.ServerConfiguration' )">
      <FormInput ref="server" id="server" v-bind:label="$t( 'label.ServerName' )" v-bind="$field( 'server' )" v-model="server"/>
      <FormInput ref="port" id="port" v-bind:label="$t( 'label.PortNumber' )" v-bind="$field( 'port' )" v-model="port"/>
      <FormDropdown ref="encryption" v-bind:label="$t( 'label.EncryptionMode' )" v-bind="$field( 'encryption' )"
                    v-bind:items="encryptionItems" v-bind:item-names="encryptionItemNames" v-model="encryption"/>
      <FormInput ref="user" id="user" v-bind:label="$t( 'label.UserName' )" v-bind="$field( 'user' )" v-model="user"/>
      <FormInput ref="password" id="password" type="password" v-bind:label="$t( 'label.Password' )" v-bind="$field( 'password' )" v-model="password"/>
      <FormInput ref="mailbox" id="mailbox" v-bind:label="$t( 'label.MailboxName' )" v-bind="$field( 'mailbox' )" v-model="mailbox"/>
      <FormCheckbox v-bind:label="$t( 'text.DoNotValidateCertificate' )" v-bind:disabled="encryption == ''" v-model="noValidate"/>
      <FormCheckbox v-bind:label="$t( 'text.LeaveProcessedMessages' )" v-bind:disabled="engine != 'imap'" v-model="leaveMessages"/>
      <div class="panel-buttons">
        <button class="btn btn-default" v-on:click="test">{{ $t( 'cmd.Test' ) }}</button>
      </div>
      <Prompt v-if="testStatus == true" path="prompt.ConnectionSuccessful" alert-class="alert-success"/>
      <Prompt v-else-if="testStatus == false" path="prompt.ConnectionFailed" alert-class="alert-danger"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.ExternalUsers' )">
      <FormCheckbox v-bind:label="$t( 'text.AcceptExternalUsers' )" v-model="allowExternal"/>
      <FormDropdown ref="robot" v-bind:label="$t( 'label.RobotUser' )" v-bind:items="userItems" v-bind:item-names="userNames"
                    v-bind:default-name="$t( 'text.None' )" v-bind:disabled="!allowExternal" v-bind="$field( 'robot' )" v-model="robot"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.TargetFolder' )">
      <FormCheckbox v-bind:label="$t( 'text.MapAddressExtensions' )" v-model="mapFolder"/>
      <FormGroup v-bind:label="$t( 'label.DefaultFolder' )" v-bind="$field( 'defaultFolder' )">
        <LocationFilters ref="defaultFolder" folder-visible v-bind:projectId.sync="projectId" v-bind:folderId.sync="defaultFolder"/>
      </FormGroup>
    </Panel>
    <Panel v-if="hasEmail" v-bind:title="$t( 'title.SendingEmails' )">
      <FormCheckbox v-bind:label="$t( 'text.SendResponses' )" v-model="respond"/>
      <FormCheckbox v-bind:label="$t( 'text.SubscribeSenders' )" v-model="subscribe"/>
    </Panel>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength, ErrorCode } from '@/constants'
import { makeParseError } from '@/utils/errors'

export default {
  props: {
    mode: String,
    inboxId: Number,
    initialEngine: String,
    initialEmail: String,
    initialDetails: Object,
    emailEngine: String
  },

  fields() {
    const details = this.mode == 'edit' ? this.initialDetails : {};

    return {
      engine: {
        value: this.initialEngine,
        type: String
      },
      email: {
        value: this.initialEmail,
        type: String,
        required: true,
        maxLength: MaxLength.Value,
        parse: this.checkEmailAddress
      },
      server: {
        value: details.server,
        type: String,
        required: true,
        maxLength: MaxLength.Value
      },
      port: {
        value: details.port,
        type: String,
        required: true,
        maxLength: 5,
        parse: this.parsePortNumber
      },
      encryption: {
        value: details.encryption,
        type: String
      },
      user: {
        value: details.user,
        type: String,
        maxLength: MaxLength.Value
      },
      password: {
        value: details.password,
        type: String,
        maxLength: MaxLength.Value
      },
      mailbox: {
        value: details.mailbox,
        type: String,
        maxLength: MaxLength.Value
      },
      noValidate: {
        value: details.noValidate,
        type: Boolean
      },
      leaveMessages: {
        value: details.leaveMessages,
        type: Boolean
      },
      allowExternal: {
        value: details.allowExternal,
        type: Boolean
      },
      robot: {
        value: details.robot,
        type: Number,
        parse: this.checkRobot
      },
      mapFolder: {
        value: details.mapFolder,
        type: Boolean
      },
      defaultFolder: {
        value: details.defaultFolder,
        type: Number,
        parse: this.checkDefaultFolder
      },
      respond: {
        value: details.respond,
        type: Boolean
      },
      subscribe: {
        value: details.subscribe,
        type: Boolean
      }
    };
  },

  data() {
    return {
      projectId: this.getInitialProjectId(),
      testStatus: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'users' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditEmailInbox' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddEmailInbox' );
    },
    hasEmail() {
      return this.emailEngine != null;
    },
    encryptionItems() {
      return [ '', 'ssl', 'tls' ];
    },
    encryptionItemNames() {
      return [ this.$t( 'text.None' ), this.$t( 'text.SSL' ), this.$t( 'text.TLS' ) ];
    },
    userItems() {
      return this.users.map( u => u.id );
    },
    userNames() {
      return this.users.map( u => u.name );
    }
  },

  methods: {
    getInitialProjectId() {
      if ( this.mode == 'edit' && this.initialDetails.defaultFolder != null ) {
        const project = this.$store.state.global.projects.find( p => p.folders.some( f => f.id == this.initialDetails.defaultFolder ) );
        if ( project != null )
          return project.id;
      }
      return null;
    },

    submit() {
      this.testStatus = null;

      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = {};

      if ( this.mode == 'edit' )
        data.inboxId = this.inboxId;
      data.engine = this.engine;
      data.email = this.email;
      data.server = this.server;
      data.port = Number( this.port );
      data.encryption = this.encryption;
      data.user = this.user;
      data.password = this.password;
      data.mailbox = this.mailbox;
      if ( this.encryption != '' )
        data.noValidate = this.noValidate;
      if ( this.engine == 'imap' )
        data.leaveMessages = this.leaveMessages;
      data.allowExternal = this.allowExternal;
      if ( this.allowExternal )
        data.robot = this.robot;
      data.mapFolder = this.mapFolder;
      data.defaultFolder = this.defaultFolder;
      if ( this.hasEmail ) {
        data.respond = this.respond;
        data.subscribe = this.subscribe;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/settings/inboxes/' + this.mode + '.php', data ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    test() {
      this.testStatus = null;

      if ( !this.$fields.validate() )
        return;

      const data = {
        engine: this.engine,
        email: this.email,
        server: this.server,
        port: Number( this.port ),
        encryption: this.encryption,
        user: this.user,
        password: this.password,
        mailbox: this.mailbox
      };

      if ( this.encryption != '' )
        data.noValidate = this.noValidate;

      this.$emit( 'block' );

      this.$ajax.post( '/settings/inboxes/test.php', data ).then( ( { status } ) => {
        this.$emit( 'unblock' );
        this.testStatus = status;
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

    checkRobot( value ) {
      if ( value == null && this.allowExternal )
        throw makeParseError( this.$t( 'ErrorCode.' + ErrorCode.EmptyValue ) );
      return value;
    },

    checkDefaultFolder( value ) {
      if ( value == null && !this.mapFolder )
        throw makeParseError( this.$t( 'ErrorCode.' + ErrorCode.EmptyValue ) );
      return value;
    },

    deleteInbox() {
      this.$router.push( 'DeleteInbox', { inboxId: this.inboxId } );
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
    this.$refs.email.focus();
  }
}
</script>
