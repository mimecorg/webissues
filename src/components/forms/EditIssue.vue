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
    <FormHeader v-bind:title="$t( 'EditIssue.Title' )" v-on:close="close"/>
    <Prompt path="EditIssue.Prompt"><strong>{{ name }}</strong></Prompt>
    <FormGroup id="name" v-bind:label="$t( 'EditIssue.Name' )" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="maxLength" v-model="nameValue">
    </FormGroup>
    <Panel v-bind:title="$t( 'EditIssue.Attributes' )">
      <FormGroup v-for="( attribute, index ) in attributes" v-bind:key="attribute.id" v-bind:id="'attribute' + attribute.id" v-bind:label="$t( 'EditIssue.AttributeLabel', [ attribute.name ] )">
        <ValueEditor v-bind:ref="'attribute' + attribute.id" v-bind:id="'attribute' + attribute.id" v-bind:attribute="getAttribute( attribute.id )"
                     v-bind:project="project" v-bind:users="users" v-model="attributeValues[ index ]"/>
      </FormGroup>
    </Panel>
    <FormButtons v-on:ok="submit" v-on:cancel="returnToDetails"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { ErrorCode, MaxLength } from '@/constants'

export default {
  props: {
    issueId: Number,
    typeId: Number,
    projectId: Number,
    name: String,
    attributes: Array
  },

  data() {
    return {
      nameValue: this.name,
      nameError: null,
      maxLength: MaxLength.Value,
      attributeValues: this.attributes.map( a => a.value )
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects', 'types', 'users' ] ),
    project() {
      if ( this.projectId != null )
        return this.projects.find( p => p.id == this.projectId );
      else
        return null;
    },
  },

  methods: {
    getAttribute( id ) {
      const type = this.types.find( t => t.id == this.typeId );
      if ( type != null )
        return type.attributes.find( a => a.id == id );
      else
        return null;
    },

    submit() {
      this.nameError = null;

      const name = this.nameValue.trim();
      if ( name == '' ) {
        this.nameError = this.$t( 'ErrorCode.' + ErrorCode.EmptyValue );
        this.$refs.name.focus();
        return;
      }

      const data = { issueId: this.issueId };
      let modified = false;

      if ( name != this.name ) {
        data.name = name;
        modified = true;
      }

      data.values = [];
      for ( let i = 0; i < this.attributes.length; i++ ) {
        const value = this.attributeValues[ i ].trim();
        if ( value != this.attributes[ i ].value ) {
          data.values.push( { id: this.attributes[ i ].id, value } );
          modified = true;
        }
      }

      if ( !modified ) {
        this.returnToDetails();
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/edit.php', data ).then( stampId => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
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
