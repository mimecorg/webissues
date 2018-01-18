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
    <FormGroup id="name" v-bind:label="$t( 'EditIssue.Name' )" v-bind:required="true" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="maxLength" v-model="nameValue">
    </FormGroup>
    <FormGroup v-if="mode == 'add' || mode == 'clone'" v-bind:label="$t( 'EditIssue.Location' )" v-bind:required="true" v-bind:error="locationError">
      <LocationFilters ref="location" v-bind:typeId="typeId" v-bind:project="project" v-bind:folder="folder" v-on:select-project="selectProject" v-on:select-folder="selectFolder"/>
    </FormGroup>
    <Panel v-if="attributes.length > 0" v-bind:title="$t( 'EditIssue.Attributes' )">
      <FormGroup v-for="( attribute, index ) in attributes" v-bind:key="attribute.id" v-bind:id="'attribute' + attribute.id" v-bind:label="$t( 'EditIssue.AttributeLabel', [ attribute.name ] )"
                 v-bind:required="isAttributeRequired( attribute.id )" v-bind:error="attributeErrors[ index ]">
        <ValueEditor ref="attribute" v-bind:id="'attribute' + attribute.id" v-bind:attribute="getAttribute( attribute.id )"
                     v-bind:project="project" v-model="attributeValues[ index ]"/>
      </FormGroup>
    </Panel>
    <FormGroup v-if="mode == 'add' || mode == 'clone'" id="description" v-bind:label="$t( 'EditIssue.Description' )" v-bind:error="descriptionError">
      <textarea ref="description" id="description" class="form-control" rows="10" v-bind:maxlength="settings.commentMaxLength" v-model="descriptionValue"></textarea>
    </FormGroup>
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
    projectId: Number,
    folderId: Number,
    name: String,
    attributes: Array,
    description: String
  },

  data() {
    return {
      nameValue: this.name,
      nameError: null,
      maxLength: MaxLength.Value,
      selectedProjectId: this.projectId,
      selectedFolderId: this.folderId,
      locationError: null,
      attributeValues: this.attributes.map( a => a.value ),
      attributeErrors: this.attributes.map( a => null ),
      descriptionValue: this.description,
      descriptionError: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects', 'types', 'users', 'settings' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditIssue.EditAttributes' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditIssue.AddIssue' );
      else if ( this.mode == 'clone' )
        return this.$t( 'EditIssue.CloneIssue' );
    },
    project() {
      if ( this.selectedProjectId != null )
        return this.projects.find( p => p.id == this.selectedProjectId );
      else
        return null;
    },
    folder() {
      if ( this.selectedFolderId != null && this.project != null )
        return this.project.folders.find( f => f.id == this.selectedFolderId );
      else
        return null;
    }
  },

  methods: {
    getAttribute( id ) {
      const type = this.types.find( t => t.id == this.typeId );
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

    selectProject( project ) {
      if ( project != null )
        this.selectedProjectId = project.id;
      else
        this.selectedProjectId = null;
      this.selectedFolderId = null;
    },
    selectFolder( folder ) {
      if ( folder != null )
        this.selectedFolderId = folder.id;
      else
        this.selectedFolderId = null;
    },

    submit() {
      this.nameError = null;
      this.attributeErrors = this.attributes.map( a => null );
      this.locationError = null;
      this.descriptionError = null;

      const data = { mode: this.mode };
      let modified = false;
      let valid = true;

      if ( this.mode == 'edit' || this.mode == 'clone' )
        data.issueId = this.issueId;

      try {
        this.nameValue = this.$parser.normalizeString( this.nameValue, MaxLength.Value );
        if ( this.mode == 'add' || this.mode == 'clone' || this.nameValue != this.name ) {
          data.name = this.nameValue;
          modified = true;
        }
      } catch ( error ) {
        if ( error.reason == 'APIError' ) {
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          if ( valid )
            this.$refs.name.focus();
          valid = false;
        } else {
          throw error;
        }
      }

      if ( this.mode == 'add' || this.mode == 'clone' ) {
        if ( this.selectedFolderId != null ) {
          data.folderId = this.selectedFolderId;
        } else {
          this.locationError = this.$t( 'EditIssue.NoFolderSelected' );
          if ( valid )
            this.$refs.location.focus();
          valid = false;
        }
      }

      data.values = [];
      for ( let i = 0; i < this.attributes.length; i++ ) {
        try {
          const attribute = this.getAttribute( this.attributes[ i ].id );
          const multiLine = attribute != null && attribute.type == 'TEXT' && attribute[ 'multi-line' ] == 1;
          this.attributeValues[ i ] = this.$parser.normalizeString( this.attributeValues[ i ], MaxLength.Value, { allowEmpty: true, multiLine } );
          if ( attribute != null )
            this.attributeValues[ i ] = this.$parser.normalizeAttributeValue( this.attributeValues[ i ], attribute, this.project );
          if ( this.attributeValues[ i ] != this.attributes[ i ].value ) {
            data.values.push( { id: this.attributes[ i ].id, value: this.attributeValues[ i ] } );
            modified = true;
          }
        } catch ( error ) {
          if ( error.reason == 'APIError' ) {
            this.attributeErrors[ i ] = this.$t( 'ErrorCode.' + error.errorCode );
            if ( valid )
              this.$refs.attribute[ i ].focus();
            valid = false;
          } else {
            throw error;
          }
        }
      }

      if ( this.mode == 'add' || this.mode == 'clone' ) {
        try {
          this.descriptionValue = this.$parser.normalizeString( this.descriptionValue, this.settings.commentMaxLength, { allowEmpty: true, multiLine: true } );
          if ( this.descriptionValue != '' )
            data.description = this.descriptionValue;
        } catch ( error ) {
          if ( error.reason == 'APIError' ) {
            this.descriptionError = this.$t( 'ErrorCode.' + error.errorCode );
            if ( valid )
              this.$refs.description.focus();
            valid = false;
          } else {
            throw error;
          }
        }
      }

      if ( !valid )
        return;

      if ( this.mode == 'edit' && !modified ) {
        this.returnToDetails( this.issueId );
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/edit.php', data ).then( ( { issueId, stampId } ) => {
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
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
