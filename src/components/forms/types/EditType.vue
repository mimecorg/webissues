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
    <Prompt v-if="mode == 'rename'" path="prompt.RenameType"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddType"></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { MaxLength, ErrorCode, Reason } from '@/constants'

export default {
  props: {
    mode: String,
    typeId: Number,
    initialName: String
  },

  fields() {
    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name
      }
    };
  },

  computed: {
    title() {
      if ( this.mode == 'rename' )
        return this.$t( 'cmd.RenameType' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddType' );
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'rename' && !this.$fields.modified() ) {
        this.returnToDetails( this.typeId );
        return;
      }

      const data = {};
      if ( this.mode == 'rename' )
        data.typeId = this.typeId;
      data.name = this.name;

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/types/' + this.mode + '.php', data ).then( ( { typeId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails( typeId );
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.TypeAlreadyExists ) {
          this.$emit( 'unblock' );
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    cancel() {
      if ( this.mode == 'rename' )
        this.returnToDetails( this.typeId );
      else
        this.$router.push( 'ManageTypes' );
    },

    returnToDetails( typeId ) {
      this.$router.push( 'TypeDetails', { typeId } );
    },

    close() {
      this.$emit( 'close' );
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
