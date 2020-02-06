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
  <BaseForm v-bind:title="$t( 'title.LogInToWebIssues' )" size="small" close-hidden>
    <template v-if="!changePassword">
      <FormInput ref="login" id="login" v-bind:label="$t( 'label.Login' )" v-bind="$field( 'login' )" v-model="login" v-on:keydown.enter="submit"/>
      <FormInput ref="password" id="password" type="password" v-bind:label="$t( 'label.Password' )" v-bind="$field( 'password' )" v-model="password" v-on:keydown.enter="submit"/>
      <div class="front-login-buttons">
        <button class="btn btn-primary" v-on:click="submit"><span class="fa fa-sign-in" aria-hidden="true"></span> {{ $t( 'cmd.LogIn' ) }}</button>
        <template v-if="anonymousAccess">
          <p>{{ $t( 'text.OR' ) }}</p>
          <button class="btn btn-default" v-on:click="startAnonymous"><span class="fa fa-user-o" aria-hidden="true"></span> {{ $t( 'cmd.AnonymousAccess' ) }}</button>
        </template>
      </div>
      <div v-if="selfRegister || resetPassword" class="form-options">
        <p v-if="selfRegister"><HyperLink v-on:click="openRegister"><span class="fa fa-user-plus" aria-hidden="true"></span> {{ $t( 'cmd.RegisterNewAccount' ) }}</HyperLink></p>
        <p v-if="resetPassword"><HyperLink v-on:click="openReset"><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'cmd.ResetPassword' ) }}</HyperLink></p>
      </div>
    </template>
    <template v-else>
      <Prompt path="prompt.EnterNewPassword"/>
      <FormInput ref="newPassword" id="newPassword" type="password" v-bind:label="$t( 'label.NewPassword' )" v-bind="$field( 'newPassword' )" v-model="newPassword"/>
      <FormInput ref="confirmPassword" id="confirmPassword" type="password" v-bind:label="$t( 'label.ConfirmPassword' )" v-bind="$field( 'confirmPassword' )" v-model="confirmPassword"/>
      <FormButtons v-on:ok="submit" v-on:cancel="cancelPassword"/>
    </template>
  </BaseForm>
</template>

<script>
import { MaxLength, ErrorCode, Reason } from '@/constants'
import { makeParseError } from '@/utils/errors'

export default {
  props: {
    anonymousAccess: Boolean,
    selfRegister: Boolean,
    resetPassword: Boolean,
    locale: String
  },

  fields() {
    return {
      login: {
        type: String,
        required: true,
        maxLength: MaxLength.Login
      },
      password: {
        type: String,
        required: true,
        maxLength: MaxLength.Password
      },
      newPassword: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        condition: () => this.changePassword
      },
      confirmPassword: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        condition: () => this.changePassword,
        parse: this.comparePassword
      }
    };
  },

  data() {
    return {
      changePassword: false
    };
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      const data = { login: this.login, password: this.password };

      if ( this.changePassword )
        data.newPassword = this.newPassword;

      this.$form.block();

      this.$ajax.post( '/login.php', data ).then( ( { userId, userName, userAccess, csrfToken, locale } ) => {
        this.$client.startApplication( { userId, userName, userAccess, csrfToken, locale } );
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.IncorrectLogin ) {
          this.$form.unblock();
          this.changePassword = false;
          this.passwordError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.password.focus();
          } );
        } else if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.MustChangePassword ) {
          this.$form.unblock();
          this.changePassword = true;
          this.$nextTick( () => {
            this.$refs.newPassword.focus();
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

    startAnonymous() {
      this.$client.startApplication( { userId: 0, userName: '', userAccess: 0, csrfToken: null, locale: this.locale } );
    },

    openRegister() {
      this.$client.openURL( this.$client.settings.baseURL + '/users/register.php' );
    },
    openReset() {
      this.$client.openURL( this.$client.settings.baseURL + '/users/password.php' );
    },

    cancelPassword() {
      this.login = null;
      this.password = null;
      this.newPassword = null;
      this.confirmPassword = null;
      this.changePassword = false;
    },

    comparePassword( value ) {
      if ( this.newPassword != '' && this.newPassword != value )
        throw makeParseError( this.$t( 'ErrorCode.' + ErrorCode.PasswordNotMatching ) );
      return value;
    }
  },

  mounted() {
    this.$refs.login.focus();
  }
}
</script>
