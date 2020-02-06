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
  <BaseForm v-bind:title="$t( 'title.ProjectPermissions' )" v-bind:breadcrumbs="breadcrumbs" auto-close save-position>
    <FormSection v-bind:title="$t( 'title.GlobalAccess' )">
      <button type="button" class="btn btn-default" v-on:click="editAccess"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
    </FormSection>
    <div class="alert alert-default">
      {{ globalAccess }}
    </div>
    <FormSection v-bind:title="$t( 'title.Members' )">
      <button type="button" class="btn btn-success" v-on:click="addMembers"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
    </FormSection>
    <Grid v-if="sortedMembers.length > 0" v-bind:items="sortedMembers" v-bind:columns="columns" v-on:row-click="rowClick">
      <template v-slot:name-cell="{ item: member }">
        {{ getName( member ) }}
      </template>
      <template v-slot:access-cell="{ item: member }">
        {{ getAccess( member ) }}
      </template>
    </Grid>
    <div v-else class="alert alert-info">
      {{ $t( 'info.NoMembers' ) }}
    </div>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    projectId: Number,
    name: String,
    public: Boolean,
    members: Array
  },

  computed: {
    ...mapState( 'global', [ 'users' ] ),
    breadcrumbs() {
      return [
        { label: this.$t( 'title.Projects' ), route: 'ManageProjects' },
        { label: this.name, route: 'ProjectDetails', params: { projectId: this.projectId } },
      ];
    },
    globalAccess() {
      return this.public ? this.$t( 'text.PublicProject' ) : this.$t( 'text.RegularProject' );
    },
    sortedMembers() {
      return this.users.map( u => this.members.find( m => m.id == u.id ) ).filter( m => m != null );
    },
    columns() {
      return {
        name: { title: this.$t( 'title.Name' ), class: 'column-large' },
        access: { title: this.$t( 'title.Access' ) }
      };
    }
  },

  methods: {
    getName( member ) {
      const user = this.users.find( u => u.id == member.id );
      if ( user != null )
        return user.name;
    },
    getAccess( member ) {
      if ( member.access == Access.NormalAccess )
        return this.$t( 'text.RegularMember' );
      else if ( member.access == Access.AdministratorAccess )
        return this.$t( 'text.ProjectAdministrator' );
    },

    editAccess() {
      this.$router.push( 'EditProjectAccess', { projectId: this.projectId } );
    },

    addMembers() {
      this.$router.push( 'AddMembers', { projectId: this.projectId } );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'EditMember', { projectId: this.projectId, userId: this.sortedMembers[ rowIndex ].id } );
    }
  }
}
</script>
