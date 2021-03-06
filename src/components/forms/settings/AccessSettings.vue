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
  <BaseForm v-bind:title="$t( 'title.AccessSettings' )" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Panel v-bind:title="$t( 'title.AnonymousAccess' )">
      <p>{{ $t( 'prompt.AnonymousAccess' ) }}</p>
      <FormCheckbox v-bind:label="$t( 'text.EnableAnonymousAccess' )" v-model="anonymousAccess"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.UserRegistration' )">
      <template v-if="hasEmail">
        <p>{{ $t( 'prompt.UserRegistration' ) }}</p>
        <FormCheckbox v-bind:label="$t( 'text.EnableUserRegistration' )" v-model="selfRegister"/>
        <template v-if="selfRegister">
          <p>{{ $t( 'prompt.UserRegistrationAutomaticApproval' ) }}</p>
          <FormCheckbox v-bind:label="$t( 'text.EnableAutomaticApproval' )" v-model="automaticApproval"/>
          <p>{{ $t( 'prompt.UserRegistrationNotifyEmail' ) }}</p>
          <FormInput ref="email" id="email" v-bind:label="$t( 'label.EmailAddress' )" v-bind="$field( 'email' )" v-bind:disabled="automaticApproval" v-model="email"/>
        </template>
      </template>
      <p v-else>{{ $t( 'prompt.UserRegistrationRequiresEmail' ) }}</p>
    </Panel>
  </BaseForm>
</template>

<script>
import { MaxLength } from '@/constants'

export default {
  props: {
    settings: Object
  },

  fields() {
    return {
      anonymousAccess: {
        value: this.settings.anonymousAccess,
        type: Boolean
      },
      selfRegister: {
        value: this.settings.selfRegister,
        type: Boolean
      },
      automaticApproval: {
        value: this.settings.registerAutoApprove,
        type: Boolean
      },
      email: {
        value: this.settings.registerNotifyEmail,
        type: String,
        maxLength: MaxLength.Value,
        parse: this.checkEmailAddress,
        condition: () => this.selfRegister && !this.automaticApproval
      }
    };
  },

  computed: {
    hasEmail() {
      return this.settings.emailEngine != null;
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = { anonymousAccess: this.anonymousAccess, selfRegister: this.selfRegister };

      if ( this.selfRegister ) {
        data.registerAutoApprove = this.automaticApproval;
        if ( !this.automaticApproval )
          data.registerNotifyEmail = this.email;
      }

      this.$form.block();

      this.$ajax.post( '/settings/access/edit.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    checkEmailAddress( value ) {
      if ( value != '' )
        this.$parser.checkEmailAddress( value );
      return value;
    },

    returnToDetails() {
      this.$router.push( 'ServerSettings' );
    }
  }
}
</script>
