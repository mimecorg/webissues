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
    <FormHeader v-bind:title="$t( 'cmd.ApproveRequest' )" v-on:close="close"/>
    <Prompt path="prompt.ApproveRequest"><strong>{{ name }}</strong></Prompt>
    <Panel v-if="projects.length > 0" v-bind:title="$t( 'title.Projects' )">
      <div slot="heading" class="panel-links">
        <HyperLink v-on:click="selectAll( true )">{{ $t( 'cmd.SelectAll' ) }}</HyperLink>
        <HyperLink v-on:click="selectAll( false )">{{ $t( 'cmd.UnselectAll' ) }}</HyperLink>
      </div>
      <div class="row checkbox-group">
        <div v-for="( u, index ) in projects" v-bind:key="u.id" class="col-xs-12 col-md-4">
          <div class="checkbox">
            <label><input type="checkbox" v-model="selectedProjects[ index ]"> {{ u.name }}</label>
          </div>
        </div>
      </div>
    </Panel>
    <Prompt v-else path="info.NoAvailableProjects" alert-class="alert-warning"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    requestId: Number,
    name: String
  },

  data() {
    return {
      selectedProjects: []
    };
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] )
  },

  methods: {
    selectAll( state ) {
      this.selectedProjects = this.projects.map( u => state );
    },

    submit() {
      this.$emit( 'block' );

      const data = { requestId: this.requestId, projects: [] };

      for ( const i in this.selectedProjects ) {
        if ( this.selectedProjects[ i ] )
          data.projects.push( this.projects[ i ].id );
      }

      this.$ajax.post( '/users/requests/approve.php', data ).then( () => {
        this.$router.push( 'RegistrationRequests' );
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
        this.$router.push( 'RequestDetails', { requestId: this.requestId } );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
