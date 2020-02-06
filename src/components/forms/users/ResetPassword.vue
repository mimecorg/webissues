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
  <BaseForm v-bind:title="$t( 'cmd.ResetPassword' )" size="small" with-buttons v-bind:cancel-hidden="!hasEmail" v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt v-if="!hasEmail" alert-class="alert-danger" path="error.ResetPasswordNoEmail"></Prompt>
    <Prompt v-else-if="isOwn" path="prompt.ResetOwnPassword"></Prompt>
    <Prompt v-else path="prompt.ResetPassword"><strong>{{ name }}</strong></Prompt>
  </BaseForm>
</template>

<script>
import { MaxLength, ErrorCode, Reason } from '@/constants'
import { makeParseError } from '@/utils/errors'

export default {
  props: {
    userId: Number,
    name: String,
    email: String,
    accountMode: Boolean
  },

  data() {
    return {
      hasEmail: this.email != null
    };
  },

  computed: {
    isOwn() {
      return this.userId == this.$store.state.global.userId;
    }
  },

  methods: {
    submit() {
      if ( !this.hasEmail ) {
        this.returnToDetails();
        return;
      }

      const data = {};
      if ( !this.isOwn )
        data.userId = this.userId;

      this.$form.block();

      this.$ajax.post( this.isOwn ? '/account/password/reset.php' : '/users/password/reset.php', data ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.UnknownUser ) {
          this.$form.unblock();
          this.hasEmail = false;
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
    }
  }
}
</script>
