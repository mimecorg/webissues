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
  <BaseForm v-bind:title="$t( 'cmd.ChangePassword' )" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt v-if="isOwn" path="prompt.ChangeOwnPassword"></Prompt>
    <Prompt v-else path="prompt.ChangePassword"><strong>{{ name }}</strong></Prompt>
    <FormInput v-if="isOwn" ref="password" id="password" type="password" v-bind:label="$t( 'label.CurrentPassword' )" v-bind="$field( 'password' )" v-model="password"/>
    <FormInput ref="newPassword" id="newPassword" type="password" v-bind:label="$t( 'label.NewPassword' )" v-bind="$field( 'newPassword' )" v-model="newPassword"/>
    <FormInput ref="confirmPassword" id="confirmPassword" type="password" v-bind:label="$t( 'label.ConfirmPassword' )" v-bind="$field( 'confirmPassword' )" v-model="confirmPassword"/>
    <FormCheckbox v-if="!isOwn" v-bind:label="$t( 'text.UserMustChangePassword' )" v-model="mustChangePassword"/>
  </BaseForm>
</template>

<script>
import { MaxLength, ErrorCode, Reason } from '@/constants'
import { makeParseError } from '@/utils/errors'

export default {
  props: {
    userId: Number,
    name: String,
    accountMode: Boolean
  },

  fields() {
    return {
      password: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        condition: this.userId == this.$store.state.global.userId
      },
      newPassword: {
        type: String,
        required: true,
        maxLength: MaxLength.Password
      },
      confirmPassword: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        parse: this.comparePassword
      },
      mustChangePassword: {
        type: Boolean
      }
    };
  },

  computed: {
    isOwn() {
      return this.userId == this.$store.state.global.userId;
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      const data = {};
      if ( this.isOwn ) {
        data.password = this.password;
        data.newPassword = this.newPassword;
      } else {
        data.userId = this.userId;
        data.password = this.newPassword;
        data.mustChangePassword = this.mustChangePassword;
      }

      this.$form.block();

      this.$ajax.post( this.isOwn ? '/account/password/edit.php' : '/users/password/edit.php', data ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.IncorrectLogin ) {
          this.$form.unblock();
          this.passwordError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.password.focus();
          } );
        } else if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.CannotReusePassword ) {
          this.$form.unblock();
          this.newPasswordError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.newPassword.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    returnToDetails() {
      if ( this.accountMode )
        this.$router.push( 'MyAccount' );
      else
        this.$router.push( 'UserDetails', { userId: this.userId } );
    },

    comparePassword( value ) {
      if ( this.newPassword != '' && this.newPassword != value )
        throw makeParseError( this.$t( 'ErrorCode.' + ErrorCode.PasswordNotMatching ) );
      return value;
    }
  },

  mounted() {
    if ( this.isOwn )
      this.$refs.password.focus();
    else
      this.$refs.newPassword.focus();
  }
}
</script>
