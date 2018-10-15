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
    <FormHeader v-bind:title="title" v-on:close="close"/>
    <Prompt v-if="mode == 'edit'" path="prompt.EditUser"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddUser"></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormInput ref="login" id="login" v-bind:label="$t( 'label.Login' )" v-bind="$field( 'login' )" v-model="login"/>
    <FormInput v-if="mode == 'add'" ref="password" id="password" type="password" v-bind:label="$t( 'label.Password' )" v-bind="$field( 'password' )" v-model="password"/>
    <FormInput v-if="mode == 'add'" ref="confirmPassword" id="confirmPassword" type="password" v-bind:label="$t( 'label.ConfirmPassword' )" v-bind="$field( 'confirmPassword' )" v-model="confirmPassword"/>
    <FormCheckbox v-if="mode == 'add'" v-bind:label="$t( 'text.UserMustChangePassword' )" v-model="mustChangePassword"/>
    <FormInput ref="email" id="email" v-bind:label="$t( 'label.EmailAddress' )" v-bind="$field( 'email' )" v-model="email"/>
    <FormGroup v-bind:label="$t( 'label.Language' )">
      <div class="dropdown-filters">
        <DropdownButton v-bind:text="languageName" class="dropdown-wide">
          <div class="dropdown-menu-scroll">
            <li v-bind:class="{ active: language == '' }">
              <HyperLink v-on:click="language = ''">{{ $t( 'text.DefaultLanguage' ) }}</HyperLink>
            </li>
            <li role="separator" class="divider"></li>
            <li v-for="l in languages" v-bind:key="l.key" v-bind:class="{ active: l.key == language }">
              <HyperLink v-on:click="language = l.key">{{ l.name }}</HyperLink>
            </li>
          </div>
        </DropdownButton>
      </div>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength, ErrorCode, Reason } from '@/constants'
import { makeParseError } from '@/utils/errors'

export default {
  props: {
    mode: String,
    userId: Number,
    initialName: String,
    initialLogin: String,
    initialEmail: String,
    initialLanguage: String
  },

  fields() {
    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name
      },
      login: {
        value: this.initialLogin,
        type: String,
        required: true,
        maxLength: MaxLength.Login
      },
      password: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        condition: this.mode == 'add'
      },
      confirmPassword: {
        type: String,
        required: true,
        maxLength: MaxLength.Password,
        parse: this.comparePassword,
        condition: this.mode == 'add'
      },
      email: {
        value: this.initialEmail != null ? this.initialEmail : '',
        type: String,
        required: false,
        maxLength: MaxLength.Value,
        parse: this.checkEmailAddress
      },
      language: {
        value: this.initialLanguage != null ? this.initialLanguage : '',
        type: String,
        required: false
      }
    };
  },

  data() {
    return {
      mustChangePassword: false
    };
  },

  computed: {
    ...mapState( 'global', [ 'languages' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditUser' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddUser' );
    },
    languageName() {
      if ( this.language == '' ) {
        return this.$t( 'text.DefaultLanguage' );
      } else {
        const language = this.languages.find( l => l.key == this.language );
        if ( language != null )
          return language.name;
      }
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails( this.userId );
        return;
      }

      const data = {};
      if ( this.mode == 'edit' )
        data.userId = this.userId;
      data.name = this.name;
      data.login = this.login;
      if ( this.mode == 'add' ) {
        data.password = this.password;
        data.mustChangePassword = this.mustChangePassword;
      }
      data.email = this.email;
      data.language = this.language;

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/users/' + this.mode + '.php', data ).then( ( { userId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails( userId );
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.UserAlreadyExists ) {
          this.$emit( 'unblock' );
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.LoginAlreadyExists ) {
          this.$emit( 'unblock' );
          this.loginError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.login.focus();
          } );
        } else if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.EmailAlreadyExists ) {
          this.$emit( 'unblock' );
          this.emailError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.email.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      if ( this.mode == 'edit' )
        this.returnToDetails( this.userId );
      else
        this.$router.push( 'ManageUsers' );
    },

    returnToDetails( userId ) {
      this.$router.push( 'UserDetails', { userId } );
    },

    close() {
      this.$emit( 'close' );
    },

    comparePassword( value ) {
      if ( this.password != '' && this.password != value )
        throw makeParseError( this.$t( 'ErrorCode.' + ErrorCode.PasswordNotMatching ) );
      return value;
    },

    checkEmailAddress( value ) {
      if ( value != '' )
        this.$parser.checkEmailAddress( value );
      return value;
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
