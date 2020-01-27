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
  <BaseForm v-bind:title="$t( 'title.GlobalAccess' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt path="prompt.EditUserAccess"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'label.Access' )" required>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="noAccess"> {{ $t( 'text.Disabled' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="normalAccess"> {{ $t( 'text.RegularUser' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="administratorAccess"> {{ $t( 'text.SystemAdministrator' ) }}</label>
      </div>
    </FormGroup>
  </BaseForm>
</template>

<script>
import { ErrorCode, Access } from '@/constants'

export default {
  props: {
    userId: Number,
    name: String,
    initialAccess: Number
  },

  fields() {
    return {
      access: {
        value: this.initialAccess,
        type: Number
      }
    };
  },

  data() {
    return {
      noAccess: Access.NoAccess,
      normalAccess: Access.NormalAccess,
      administratorAccess: Access.AdministratorAccess
    };
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = { userId: this.userId, access: this.access };

      this.$form.block();

      this.$ajax.post( '/users/access.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'UserDetails', { userId: this.userId } );
    }
  }
}
</script>
