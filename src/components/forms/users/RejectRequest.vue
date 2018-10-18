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
    <FormHeader v-bind:title="$t( 'cmd.RejectRequest' )" v-on:close="close"/>
    <Prompt path="prompt.RejectRequest"><strong>{{ name }}</strong></Prompt>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  props: {
    requestId: Number,
    name: String
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      const data = { requestId: this.requestId };

      this.$ajax.post( '/users/requests/reject.php', data ).then( () => {
        this.$router.push( 'RegistrationRequests' );
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
        this.$router.push( 'RequestDetails', { requestId: this.requestId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
