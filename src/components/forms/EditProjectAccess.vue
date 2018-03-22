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
    <FormHeader v-bind:title="$t( 'EditProjectAccess.EditProjectAccess' )" v-on:close="close"/>
    <Prompt path="EditProjectAccess.EditProjectAccessPrompt"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'EditProjectAccess.Access' )" v-bind:required="true">
      <div class="radio">
        <label><input type="radio" v-model="publicValue" v-bind:value="false"> {{ $t( 'EditProjectAccess.RegularProject' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="publicValue" v-bind:value="true"> {{ $t( 'EditProjectAccess.PublicProject' ) }}</label>
      </div>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { ErrorCode } from '@/constants'

export default {
  props: {
    projectId: Number,
    name: String,
    public: Boolean
  },

  data() {
    return {
      publicValue: this.public
    };
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      if ( this.publicValue == this.public ) {
        this.returnToDetails();
        return;
      }

      const data = { projectId: this.projectId, public: this.publicValue };

      this.$ajax.post( '/server/api/project/access.php', data ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ProjectPermissions', { projectId: this.projectId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
