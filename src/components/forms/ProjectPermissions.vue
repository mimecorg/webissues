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
    <FormHeader v-bind:title="$t( 'ProjectPermissions.ProjectPermissions' )" v-on:close="close">
      <button type="button" class="btn btn-default" v-on:click="returnToDetails"><span class="fa fa-arrow-left" aria-hidden="true"></span> {{ $t( 'ProjectPermissions.Return' ) }}</button>
    </FormHeader>
    <Prompt path="ProjectPermissions.ProjectPermissionsPrompt"><strong>{{ name }}</strong></Prompt>
    <FormSection v-bind:title="$t( 'ProjectPermissions.GlobalAccess' )">
      <button type="button" class="btn btn-default"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'ProjectPermissions.Edit' ) }}</button>
    </FormSection>
    <div class="description-panel">
      {{ globalAccess }}
    </div>
    <FormSection v-bind:title="$t( 'ProjectPermissions.Members' )">
      <button type="button" class="btn btn-success"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'ProjectPermissions.Add' ) }}</button>
    </FormSection>
    <Grid v-bind:items="members" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-wide', null ]">
      <template slot-scope="{ item, columnIndex, columnClass }">
        <td v-bind:class="columnClass">{{ getCellValue( columnIndex, item ) }}</td>
      </template>
    </Grid>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

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
    globalAccess() {
      return this.public ? this.$t( 'ProjectPermissions.PublicProject' ) : this.$t( 'ProjectPermissions.RegularProject' );
    },
    columnNames() {
      return [
        this.$t( 'ProjectPermissions.Name' ),
        this.$t( 'ProjectPermissions.Access' )
      ];
    }
  },
  methods: {
    getCellValue( columnIndex, member ) {
      switch ( columnIndex ) {
        case 0:
          const user = this.users.find( u => u.id == member.id );
          if ( user != null )
            return user.name;
        case 1:
          if ( member.access == Access.NormalAccess )
            return this.$t( 'ProjectPermissions.RegularMember' );
          else if ( member.access == Access.AdministratorAccess )
            return this.$t( 'ProjectPermissions.ProjectAdministrator' );
          break;
      }
    },
    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.projectId } );
    },
    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
