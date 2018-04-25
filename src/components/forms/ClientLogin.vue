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
    <div class="form-header">
      <h1>{{ $t( 'ClientLogin.LogInToWebIssues' ) }}</h1>
    </div>
    <FormInput ref="login" id="login" v-bind:label="$t( 'ClientLogin.Login' )" v-bind="$field( 'login' )" v-model="login" v-on:keydown.enter="submit"/>
    <FormInput ref="password" id="password" type="password" v-bind:label="$t( 'ClientLogin.Password' )" v-bind="$field( 'password' )" v-model="password" v-on:keydown.enter="submit"/>
    <div class="front-login-buttons">
      <button class="btn btn-primary" v-on:click="submit"><span class="fa fa-sign-in" aria-hidden="true"></span> {{ $t( 'ClientLogin.LogIn' ) }}</button>
      <template v-if="anonymousAccess">
        <p>{{ $t( 'ClientLogin.OR' ) }}</p>
        <button class="btn btn-default" v-on:click="startAnonymous"><span class="fa fa-user-o" aria-hidden="true"></span> {{ $t( 'ClientLogin.AnonymousAccess' ) }}</button>
      </template>
    </div>
    <div v-if="selfRegister" class="form-options">
      <p><HyperLink v-on:click="openRegister"><span class="fa fa-user-plus" aria-hidden="true"></span> {{ $t( 'ClientLogin.RegisterNewAccount' ) }}</HyperLink></p>
    </div>
  </div>
</template>

<script>
import { MaxLength, ErrorCode } from '@/constants'

export default {
  props: {
    anonymousAccess: Boolean,
    selfRegister: Boolean
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
      }
    };
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      const data = { login: this.login, password: this.password };

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/login.php', data ).then( ( { userId, userName, userAccess, csrfToken } ) => {
        this.$client.startApplication( { userId, userName, userAccess, csrfToken } );
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.IncorrectLogin ) {
          this.$emit( 'unblock' );
          this.passwordError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.password.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    startAnonymous() {
      this.$client.startApplication( { userId: 0, userName: '', userAccess: 0, csrfToken: null } );
    },

    openRegister() {
      this.$client.openExternal( this.$client.settings.baseURL + '/users/register.php' );
    },
  },

  mounted() {
    this.$refs.login.focus();
  }
}
</script>
