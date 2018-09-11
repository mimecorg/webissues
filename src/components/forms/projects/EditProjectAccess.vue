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
    <FormHeader v-bind:title="$t( 'title.GlobalAccess' )" v-on:close="close"/>
    <Prompt path="prompt.EditProjectAccess"><strong>{{ name }}</strong></Prompt>
    <FormGroup v-bind:label="$t( 'label.Access' )" required>
      <div class="radio">
        <label><input type="radio" v-model="public" v-bind:value="false"> {{ $t( 'text.RegularProject' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="public" v-bind:value="true"> {{ $t( 'text.PublicProject' ) }}</label>
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
    initialPublic: Boolean
  },

  data() {
    return {
      public: this.initialPublic
    };
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      if ( this.public == this.initialPublic ) {
        this.returnToDetails();
        return;
      }

      const data = { projectId: this.projectId, public: this.public };

      this.$ajax.post( '/server/api/projects/access.php', data ).then( () => {
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
