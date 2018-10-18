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
      <button v-if="mode == 'edit' && canEdit" type="button" class="btn btn-default" v-on:click="removeMember">
        <span class="fa fa-ban" aria-hidden="true"></span> {{ $t( 'cmd.Remove' ) }}
      </button>
    </FormHeader>
    <Prompt v-if="mode == 'edit'" path="prompt.EditMember"><strong>{{ projectName }}</strong><strong>{{ userName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddMembers"><strong>{{ projectName }}</strong></Prompt>
    <Panel v-if="mode == 'add' && availableUsers.length > 0" v-bind:title="$t( 'title.Users' )">
      <div slot="heading" class="panel-links">
        <HyperLink v-on:click="selectAll( true )">{{ $t( 'cmd.SelectAll' ) }}</HyperLink>
        <HyperLink v-on:click="selectAll( false )">{{ $t( 'cmd.UnselectAll' ) }}</HyperLink>
      </div>
      <div class="row checkbox-group">
        <div v-for="( u, index ) in availableUsers" v-bind:key="u.id" class="col-xs-12 col-md-4">
          <div class="checkbox">
            <label><input type="checkbox" v-model="selectedUsers[ index ]"> {{ u.name }}</label>
          </div>
        </div>
      </div>
    </Panel>
    <Prompt v-else-if="mode == 'add'" path="info.NoAvailableUsers" alert-class="alert-warning"/>
    <FormGroup v-if="canEdit" v-bind:label="$t( 'title.Access' )" required>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="normalAccess"> {{ $t( 'text.RegularMember' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="administratorAccess"> {{ $t( 'text.ProjectAdministrator' ) }}</label>
      </div>
    </FormGroup>
    <Prompt v-else path="error.CannotEditOwnAcess" alert-class="alert-warning"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    mode: String,
    projectId: Number,
    userId: Number,
    projectName: String,
    userName: String,
    initialAccess: Number,
    members: Array
  },

  data() {
    return {
      normalAccess: Access.NormalAccess,
      administratorAccess: Access.AdministratorAccess,
      access: this.initialAccess,
      selectedUsers: []
    };
  },

  computed: {
    ...mapState( 'global', [ 'users' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditMember' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddMembers' );
    },
    canEdit() {
      return this.mode == 'add' || this.$store.state.global.userAccess == Access.AdministratorAccess || this.userId != this.$store.state.global.userId;
    },
    availableUsers() {
      return this.members != null ? this.users.filter( u => this.members.every( m => m.id != u.id ) ) : [];
    }
  },

  methods: {
    removeMember() {
      this.$router.push( 'RemoveMember', { projectId: this.projectId, userId: this.userId } );
    },

    selectAll( state ) {
      this.selectedUsers = this.availableUsers.map( u => state );
    },

    submit() {
      this.$emit( 'block' );

      const data = { projectId: this.projectId };

      if ( this.mode == 'edit' ) {
        data.users = [ this.userId ];
      } else {
        data.users = [];
        for ( const i in this.selectedUsers ) {
          if ( this.selectedUsers[ i ] )
            data.users.push( this.availableUsers[ i ].id );
        }
      }

      data.access = this.access;

      if ( this.mode == 'edit' && this.access == this.initialAccess || data.users.length == 0 ) {
        this.returnToDetails();
        return;
      }

      this.$ajax.post( '/projects/members/edit.php', data ).then( () => {
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
