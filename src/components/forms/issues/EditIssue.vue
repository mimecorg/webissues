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
    <Prompt v-if="mode == 'edit'" path="prompt.EditAttributes"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddIssue"/>
    <Prompt v-else-if="mode == 'clone'" path="prompt.CloneIssue"><strong>{{ initialName }}</strong></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormGroup v-if="mode == 'add' || mode == 'clone'" v-bind:label="$t( 'label.Location' )" v-bind="$field( 'folderId' )">
      <LocationFilters ref="folderId" v-bind:typeId="typeId" v-bind:projectId.sync="projectId" v-bind:folderId.sync="folderId" folder-visible/>
    </FormGroup>
    <Panel v-if="attributes.length > 0" v-bind:title="$t( 'title.Attributes' )">
      <FormGroup v-for="( attribute, index ) in attributes" v-bind:key="attribute.id" v-bind:id="'attribute' + attribute.id"
                 v-bind:label="$t( 'label.Attribute', [ getAttributeName( attribute.id ) ] )"
                 v-bind:required="isAttributeRequired( attribute.id )" v-bind:error="$data[ 'attribute' + attribute.id + 'Error' ]">
        <ValueEditor ref="attribute" v-bind:id="'attribute' + attribute.id" v-bind:attribute="getAttribute( attribute.id )"
                     v-bind:project="project" v-model="$data[ 'attribute' + attribute.id ]"/>
      </FormGroup>
    </Panel>
    <MarkupEditor v-if="mode == 'add' || mode == 'clone'" ref="description" id="description" v-bind:label="$t( 'label.Description' )" v-bind="$field( 'description' )"
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
        requiredError: this.$t( 'error.NoFolderSelected' )
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

    const type = this.$store.state.global.types.find( t => t.id == this.typeId );

    for ( let i = 0; i < this.attributes.length; i++ ) {
      const field = {
        value: this.attributes[ i ].value,
        type: String,
        required: false,
        maxLength: MaxLength.Value
      };
      const id = this.attributes[ i ].id;
      if ( type != null ) {
        const attribute = type.attributes.find( a => a.id == id );
        if ( attribute != null ) {
          field.value = this.$formatter.convertAttributeValue( field.value, attribute, { multiLine: true } );
          field.multiLine = attribute.type == 'TEXT' && attribute[ 'multi-line' ] == 1;
          field.parse = value => this.$parser.normalizeAttributeValue( value, attribute, this.project );
        }
      }
      field.focus = () => this.$refs.attribute[ i ].focus();
      result[ 'attribute' + id ] = field;
    }

    return result;
  },

  data() {
    return {
      projectId: this.getInitialProjectId(),
      descriptionFormat: this.initialFormat
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects', 'types' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditAttributes' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddIssue' );
      else if ( this.mode == 'clone' )
        return this.$t( 'cmd.CloneIssue' );
    },
    project() {
      if ( this.projectId != null )
        return this.projects.find( p => p.id == this.projectId );
      else
        return null;
    },
    type() {
      return this.types.find( t => t.id == this.typeId );
    }
  },

  methods: {
    getInitialProjectId() {
      if ( this.initialProjectId != null ) {
        return this.initialProjectId;
      } else if ( this.initialFolderId != null ) {
        const project = this.$store.state.global.projects.find( p => p.folders.some( f => f.id == this.initialFolderId ) );
        if ( project != null )
          return project.id;
      }
      return null;
    },

    getAttribute( id ) {
      if ( this.type != null )
        return this.type.attributes.find( a => a.id == id );
      else
        return null;
    },
    getAttributeName( id ) {
      const attribute = this.getAttribute( id );
      if ( attribute != null )
        return attribute.name;
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
        if ( value != this.attributes[ i ].value ) {
          const attribute = this.getAttribute( id );
          const convertedValue = this.$parser.convertAttributeValue( value, attribute );
          data.values.push( { id, value: convertedValue } );
        }
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
