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
    <Prompt v-if="mode == 'edit'" path="EditIssue.EditAttributesPrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditIssue.AddIssuePrompt"/>
    <Prompt v-else-if="mode == 'clone'" path="EditIssue.CloneIssuePrompt"><strong>{{ name }}</strong></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'EditIssue.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormGroup v-if="mode == 'add' || mode == 'clone'" v-bind:label="$t( 'EditIssue.Location' )" v-bind="$field( 'folderId' )">
      <LocationFilters ref="folderId" v-bind:typeId="typeId" v-bind:projectId.sync="projectId" v-bind:folderId.sync="folderId" folder-visible/>
    </FormGroup>
    <Panel v-if="attributes.length > 0" v-bind:title="$t( 'EditIssue.Attributes' )">
      <FormGroup v-for="( attribute, index ) in attributes" v-bind:key="attribute.id" v-bind:id="'attribute' + attribute.id" v-bind:label="$t( 'EditIssue.AttributeLabel', [ attribute.name ] )"
                 v-bind:required="isAttributeRequired( attribute.id )" v-bind:error="$data[ 'attribute' + attribute.id + 'Error' ]">
        <ValueEditor ref="attribute" v-bind:id="'attribute' + attribute.id" v-bind:attribute="getAttribute( attribute.id )"
                     v-bind:project="project" v-model="$data[ 'attribute' + attribute.id ]"/>
      </FormGroup>
    </Panel>
    <MarkupEditor v-if="mode == 'add' || mode == 'clone'" ref="description" id="description" v-bind:label="$t( 'EditIssue.Description' )" v-bind="$field( 'description' )"
                  v-bind:format.sync="descriptionFormat" v-model="description" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength } from '@/constants'

export default {
  props: {
    mode: String,
    issueId: Number,
    typeId: Number,
    initialProjectId: Number,
    initialFolderId: Number,
    initialName: String,
    attributes: Array,
    initialDescription: String,
    initialFormat: Number
  },

  fields() {
    const result = {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Value
      },
      folderId: {
        condition: this.mode == 'add' || this.mode == 'clone',
        value: this.initialFolderId,
        type: Number,
        required: true,
        requiredError: this.$t( 'EditIssue.NoFolderSelected' )
      },
      description: {
        condition: this.mode == 'add' || this.mode == 'clone',
        value: this.initialDescription,
        type: String,
        required: false,
        maxLength: this.$store.state.global.settings.commentMaxLength,
        multiLine: true
      }
    };

    for ( let i = 0; i < this.attributes.length; i++ ) {
      const field = {
        value: this.attributes[ i ].value,
        type: String,
        required: false,
        maxLength: MaxLength.Value
      };
      const id = this.attributes[ i ].id;
      const attribute = this.getAttribute( id );
      if ( attribute != null ) {
        field.multiLine = attribute.type == 'TEXT' && attribute[ 'multi-line' ] == 1;
        field.parse = value => this.$parser.normalizeAttributeValue( value, attribute, this.project );
      }
      field.focus = () => this.$refs.attribute[ i ].focus();
      result[ 'attribute' + id ] = field;
    }

    return result;
  },

  data() {
    return {
      projectId: this.initialProjectId,
      descriptionFormat: this.initialFormat
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditIssue.EditAttributes' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditIssue.AddIssue' );
      else if ( this.mode == 'clone' )
        return this.$t( 'EditIssue.CloneIssue' );
    },
    project() {
      if ( this.projectId != null )
        return this.projects.find( p => p.id == this.projectId );
      else
        return null;
    }
  },

  methods: {
    getAttribute( id ) {
      const type = this.$store.state.global.types.find( t => t.id == this.typeId );
      if ( type != null )
        return type.attributes.find( a => a.id == id );
      else
        return null;
    },
    isAttributeRequired( id ) {
      const attribute = this.getAttribute( id );
      if ( attribute != null )
        return attribute.required == 1;
      else
        return false;
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails( this.issueId );
        return;
      }

      const data = {};
      if ( this.mode == 'edit' || this.mode == 'clone' )
        data.issueId = this.issueId;
      if ( this.mode == 'add' || this.mode == 'clone' || this.name != this.initialName )
          data.name = this.name;
      if ( this.mode == 'add' || this.mode == 'clone' )
        data.folderId = this.folderId;

      data.values = [];
      for ( let i = 0; i < this.attributes.length; i++ ) {
        const id = this.attributes[ i ].id;
        const value = this[ 'attribute' + id ];
        if ( value != this.attributes[ i ].value )
          data.values.push( { id, value } );
      }

      if ( ( this.mode == 'add' || this.mode == 'clone' ) && this.description != '' ) {
        data.description = this.description;
        data.descriptionFormat = this.descriptionFormat;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issues/' + this.mode + '.php', data ).then( ( { issueId, stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails( issueId );
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      if ( this.mode == 'edit' || this.mode == 'clone' )
        this.returnToDetails( this.issueId );
      else
        this.close();
    },

    returnToDetails( issueId ) {
      this.$router.push( 'IssueDetails', { issueId } );
    },

    close() {
      this.$emit( 'close' );
    },
    error( error ) {
      this.$emit( 'error', error );
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
