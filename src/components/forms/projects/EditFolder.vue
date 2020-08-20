<!--
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
  <BaseForm v-bind:title="title" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <template v-slot:header>
      <DropdownButton v-if="mode == 'rename'" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="moveFolder"><span class="fa fa-exchange" aria-hidden="true"></span> {{ $t( 'cmd.MoveFolder' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="deleteFolder"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.DeleteFolder' ) }}</HyperLink></li>
      </DropdownButton>
    </template>
    <Prompt v-if="mode == 'rename'" path="prompt.RenameFolder"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddFolder"><strong>{{ projectName }}</strong></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormGroup v-if="mode == 'add'" v-bind:label="$t( 'label.Type' )" v-bind="$field( 'typeId' )">
      <div class="dropdown-filters">
        <DropdownFilterButton ref="type" fa-class="fa-table" v-bind:text="typeName" v-bind:title="typeTitle" v-bind:filter.sync="typesFilter">
          <li v-bind:class="{ active: type == null }">
            <HyperLink v-on:click="selectType( null )">{{ $t( 'text.SelectType' ) }}</HyperLink>
          </li>
          <template v-if="filteredTypes.length > 0">
            <li role="separator" class="divider"></li>
            <li v-for="t in filteredTypes" v-bind:key="t.id" v-bind:class="{ active: type != null && t.id == type.id }">
              <HyperLink v-on:click="selectType( t )">{{ t.name }}</HyperLink>
            </li>
          </template>
        </DropdownFilterButton>
      </div>
    </FormGroup>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

import { MaxLength, ErrorCode, Reason } from '@/constants'
import filterItems from '@/utils/filter'

export default {
  props: {
    mode: String,
    projectId: Number,
    projectName: String,
    folderId: Number,
    initialName: String
  },

  data() {
    return {
      typesFilter: '',
    };
  },

  fields() {
    return {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name
      },
      typeId: {
        condition: this.mode == 'add',
        type: Number,
        required: true,
        requiredError: this.$t( 'error.NoTypeSelected' )
      }
    };
  },

  computed: {
    ...mapState( 'global', [ 'types' ] ),
    filteredTypes() {
      return filterItems( this.types, this.typesFilter );
    },
    title() {
      if ( this.mode == 'rename' )
        return this.$t( 'cmd.RenameFolder' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddFolder' );
    },
    type() {
      if ( this.typeId != null )
        return this.types.find( t => t.id == this.typeId );
      else
        return null;
    },
    typeName() {
      if ( this.type != null )
        return this.type.name;
      else
        return this.$t( 'text.SelectType' );
    },
    typeTitle() {
      if ( this.type != null )
        return this.$t( 'text.Type', [ this.typeName ] );
      else
        return this.$t( 'text.SelectType' );
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
      if ( type != null )
        this.typeId = type.id;
      else
        this.typeId = null;
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'rename' && !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = {};
      if ( this.mode == 'add' )
        data.projectId = this.projectId;
      else
        data.folderId = this.folderId;
      data.name = this.name;
      if ( this.mode == 'add' )
        data.typeId = this.typeId;

      this.$form.block();

      this.$ajax.post( '/projects/folders/' + this.mode + '.php', data ).then( ( { folderId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.FolderAlreadyExists ) {
          this.$form.unblock();
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.projectId } );
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
