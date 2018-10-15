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
      <button v-if="mode == 'edit'" type="button" class="btn btn-default" v-on:click="removeProject">
        <span class="fa fa-ban" aria-hidden="true"></span> {{ $t( 'cmd.Remove' ) }}
      </button>
    </FormHeader>
    <Prompt v-if="mode == 'edit'" path="prompt.EditMember"><strong>{{ projectName }}</strong><strong>{{ userName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddUserProjects"><strong>{{ userName }}</strong></Prompt>
    <Panel v-if="mode == 'add' && availableProjects.length > 0" v-bind:title="$t( 'title.Projects' )">
      <div slot="heading" class="panel-links">
        <HyperLink v-on:click="selectAll( true )">{{ $t( 'cmd.SelectAll' ) }}</HyperLink>
        <HyperLink v-on:click="selectAll( false )">{{ $t( 'cmd.UnselectAll' ) }}</HyperLink>
      </div>
      <div class="row checkbox-group">
        <div v-for="( u, index ) in availableProjects" v-bind:key="u.id" class="col-xs-12 col-md-4">
          <div class="checkbox">
            <label><input type="checkbox" v-model="selectedProjects[ index ]"> {{ u.name }}</label>
          </div>
        </div>
      </div>
    </Panel>
    <Prompt v-else-if="mode == 'add'" path="info.NoAvailableProjects" alert-class="alert-warning"/>
    <FormGroup v-bind:label="$t( 'title.Access' )" required>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="normalAccess"> {{ $t( 'text.RegularMember' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="access" v-bind:value="administratorAccess"> {{ $t( 'text.ProjectAdministrator' ) }}</label>
      </div>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    mode: String,
    userId: Number,
    projectId: Number,
    userName: String,
    projectName: String,
    initialAccess: Number,
    userProjects: Array
  },

  data() {
    return {
      normalAccess: Access.NormalAccess,
      administratorAccess: Access.AdministratorAccess,
      access: this.initialAccess,
      selectedProjects: []
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditAccess' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddProjects' );
    },
    availableProjects() {
      return this.userProjects != null ? this.projects.filter( p => this.userProjects.every( up => up.id != p.id ) ) : [];
    }
  },

  methods: {
    removeProject() {
      this.$router.push( 'RemoveUserProject', { userId: this.userId, projectId: this.projectId } );
    },

    selectAll( state ) {
      this.selectedProjects = this.availableProjects.map( u => state );
    },

    submit() {
      this.$emit( 'block' );

      const data = { userId: this.userId };

      if ( this.mode == 'edit' ) {
        data.projects = [ this.projectId ];
      } else {
        data.projects = [];
        for ( const i in this.selectedProjects ) {
          if ( this.selectedProjects[ i ] )
            data.projects.push( this.availableProjects[ i ].id );
        }
      }

      data.access = this.access;

      if ( this.mode == 'edit' && this.access == this.initialAccess || data.projects.length == 0 ) {
        this.returnToDetails();
        return;
      }

      this.$ajax.post( '/server/api/users/projects/edit.php', data ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'UserDetails', { userId: this.userId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
