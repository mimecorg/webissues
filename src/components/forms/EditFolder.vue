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
    <FormHeader v-bind:title="title" v-on:close="close">
      <DropdownButton v-if="mode == 'rename'" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'EditFolder.More' )">
        <li><HyperLink v-on:click="moveFolder"><span class="fa fa-exchange" aria-hidden="true"></span> {{ $t( 'EditFolder.MoveFolder' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteFolder"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'EditFolder.DeleteFolder' ) }}</HyperLink></li>
      </DropdownButton>
    </FormHeader>
    <Prompt v-if="mode == 'rename'" path="EditFolder.RenameFolderPrompt"><strong>{{ name }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditFolder.AddFolderPrompt"><strong>{{ projectName }}</strong></Prompt>
    <FormGroup id="name" v-bind:label="$t( 'EditFolder.Name' )" v-bind:required="true" v-bind:error="nameError">
      <input ref="name" id="name" type="text" class="form-control" v-bind:maxlength="nameMaxLength" v-model="nameValue">
    </FormGroup>
    <FormGroup v-if="mode == 'add'" v-bind:label="$t( 'EditFolder.Type' )" v-bind:required="true" v-bind:error="typeError">
      <div class="dropdown-filters">
        <DropdownButton ref="type" fa-class="fa-list" v-bind:text="typeName" v-bind:title="typeTitle">
          <div class="dropdown-menu-scroll">
            <li v-bind:class="{ active: type == null }">
              <HyperLink v-on:click="selectType( null )">{{ $t( 'EditFolder.SelectType' ) }}</HyperLink>
            </li>
            <template v-if="types.length > 0">
              <li role="separator" class="divider"></li>
              <li v-for="t in types" v-bind:key="t.id" v-bind:class="{ active: type != null && t.id == type.id }">
                <HyperLink v-on:click="selectType( t )">{{ t.name }}</HyperLink>
              </li>
            </template>
          </div>
        </DropdownButton>
      </div>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength, ErrorCode } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    projectName: String,
    folderId: Number,
    name: String
  },

  data() {
    return {
      nameValue: this.name,
      nameMaxLength: MaxLength.Name,
      nameError: null,
      type: null,
      typeError: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'types', 'settings' ] ),
    title() {
      if ( this.mode == 'rename' )
        return this.$t( 'EditFolder.RenameFolder' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditFolder.AddFolder' );
    },
    typeName() {
      if ( this.type != null )
        return this.type.name;
      else
        return this.$t( 'EditFolder.SelectType' );
    },
    typeTitle() {
      if ( this.type != null )
        return this.$t( 'EditFolder.TypeTitle', [ this.typeName ] );
      else
        return this.$t( 'EditFolder.SelectType' );
    }
  },

  methods: {
    moveFolder() {
      this.$router.push( 'MoveFolder', { projectId: this.projectId, folderId: this.folderId } );
    },
    deleteFolder() {
      this.$router.push( 'DeleteFolder', { projectId: this.projectId, folderId: this.folderId } );
    },

    selectType( type ) {
      this.type = type;
    },

    submit() {
      this.nameError = null;
      this.typeError = null;

      const data = {};
      if ( this.mode == 'add' )
        data.projectId = this.projectId;
      else
        data.folderId = this.folderId;
      let modified = false;
      let valid = true;

      try {
        this.nameValue = this.$parser.normalizeString( this.nameValue, MaxLength.Name, { allowEmpty: false } );
        if ( this.mode == 'add' || this.nameValue != this.name )
          modified = true;
        data.name = this.nameValue;
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

      if ( this.mode == 'add' ) {
        if ( this.type != null ) {
          data.typeId = this.type.id;
        } else {
          this.typeError = this.$t( 'EditFolder.NoTypeSelected' );
          if ( valid )
            this.$refs.type.focus();
          valid = false;
        }
      }

      if ( !valid )
        return;

      if ( this.mode == 'rename' && !modified ) {
        this.returnToDetails();
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/project/folder/' + this.mode + '.php', data ).then( ( { folderId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == 'APIError' && error.errorCode == ErrorCode.FolderAlreadyExists ) {
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
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.projectId } );
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
