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
  <BaseForm v-bind:title="title" v-bind:size="size" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <template v-slot:header>
      <button v-if="mode == 'edit'" type="button" class="btn btn-default" v-on:click="removeProject">
        <span class="fa fa-ban" aria-hidden="true"></span> {{ $t( 'cmd.Remove' ) }}
      </button>
    </template>
    <Prompt v-if="mode == 'edit'" path="prompt.EditMember"><strong>{{ projectName }}</strong><strong>{{ userName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddUserProjects"><strong>{{ userName }}</strong></Prompt>
    <Panel v-if="mode == 'add' && availableProjects.length > 0" v-bind:title="$t( 'title.Projects' )">
      <template v-slot:heading>
        <div class="panel-links">
          <HyperLink v-on:click="selectAll( true )">{{ $t( 'cmd.SelectAll' ) }}</HyperLink>
          <HyperLink v-on:click="selectAll( false )">{{ $t( 'cmd.UnselectAll' ) }}</HyperLink>
        </div>
      </template>
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
  </BaseForm>
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
    userProjects: Array,
    accountMode: Boolean
  },

  fields() {
    return {
      access: {
        value: this.initialAccess,
        type: Number
      }
    };
  },

  data() {
    return {
      normalAccess: Access.NormalAccess,
      administratorAccess: Access.AdministratorAccess,
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
    size() {
      if ( this.mode == 'edit' )
        return 'small';
      else if ( this.mode == 'add' )
        return 'normal';
    },
    availableProjects() {
      return this.userProjects != null ? this.projects.filter( p => this.userProjects.every( up => up.id != p.id ) ) : [];
    }
  },

  methods: {
    removeProject() {
      this.$router.push( this.accountMode ? 'RemoveAccountProject' : 'RemoveUserProject', { userId: this.userId, projectId: this.projectId } );
    },

    selectAll( state ) {
      this.selectedProjects = this.availableProjects.map( u => state );
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

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

      if ( data.projects.length == 0 ) {
        this.returnToDetails();
        return;
      }

      this.$form.block();

      this.$ajax.post( '/users/projects/edit.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      if ( this.accountMode )
        this.$router.push( 'MyAccount' );
      else
        this.$router.push( 'UserDetails', { userId: this.userId } );
    }
  }
}
</script>
