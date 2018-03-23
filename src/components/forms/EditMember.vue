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
        <span class="fa fa-ban" aria-hidden="true"></span> {{ $t( 'EditMember.Remove' ) }}
      </button>
    </FormHeader>
    <Prompt v-if="mode == 'edit'" path="EditMember.EditMemberPrompt"><strong>{{ projectName }}</strong><strong>{{ userName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditMember.AddMembersPrompt"><strong>{{ projectName }}</strong></Prompt>
    <Panel v-if="mode == 'add' && availableUsers.length > 0" v-bind:title="$t( 'EditMember.Users' )">
      <div slot="heading" class="panel-links">
        <HyperLink v-on:click="selectAll( true )">{{ $t( 'EditMember.SelectAll' ) }}</HyperLink>
        <HyperLink v-on:click="selectAll( false )">{{ $t( 'EditMember.UnselectAll' ) }}</HyperLink>
      </div>
      <div class="row checkbox-group">
        <div v-for="( u, index ) in availableUsers" v-bind:key="u.id" class="col-xs-12 col-md-4">
          <div class="checkbox">
            <label><input type="checkbox" v-model="userSelected[ index ]"> {{ u.name }}</label>
          </div>
        </div>
      </div>
    </Panel>
    <Prompt v-else-if="mode == 'add'" path="EditMember.NoAvailableUsers" alert-class="alert-warning"/>
    <FormGroup v-if="canEdit" v-bind:label="$t( 'EditMember.Access' )" v-bind:required="true">
      <div class="radio">
        <label><input type="radio" v-model="accessValue" v-bind:value="normalAccess"> {{ $t( 'EditMember.RegularMember' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="accessValue" v-bind:value="administratorAccess"> {{ $t( 'EditMember.ProjectAdministrator' ) }}</label>
      </div>
    </FormGroup>
    <Prompt v-else path="EditMember.CannotEditOwnAcess" alert-class="alert-warning"/>
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
    access: Number,
    members: Array
  },

  data() {
    return {
      normalAccess: Access.NormalAccess,
      administratorAccess: Access.AdministratorAccess,
      accessValue: this.access,
      userSelected: []
    };
  },

  computed: {
    ...mapState( 'global', [ 'users' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditMember.EditMember' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditMember.AddMembers' );
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
      this.userSelected = this.availableUsers.map( u => state );
    },

    submit() {
      this.$emit( 'block' );

      const data = { projectId: this.projectId };

      if ( this.mode == 'edit' ) {
        data.users = [ this.userId ];
      } else {
        data.users = [];
        for ( const i in this.userSelected ) {
          if ( this.userSelected[ i ] )
            data.users.push( this.availableUsers[ i ].id );
        }
      }

      data.access = this.accessValue;

      if ( this.mode == 'edit' && this.accessValue == this.access || data.users.length == 0 ) {
        this.returnToDetails();
        return;
      }

      this.$ajax.post( '/server/api/projects/members/edit.php', data ).then( () => {
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
