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
  <BaseForm v-bind:title="name" v-bind:breadcrumbs="breadcrumbs" auto-close save-position>
    <template slot="header">
      <DropdownButton v-if="canResetPassword" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="changePassword"><span class="fa fa-key" aria-hidden="true"></span> {{ $t( 'cmd.ChangePassword' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="resetPassword"><span class="fa fa-unlock-alt" aria-hidden="true"></span> {{ $t( 'cmd.ResetPassword' ) }}</HyperLink></li>
      </DropdownButton>
      <button v-else type="button" class="btn btn-default" v-on:click="changePassword"><span class="fa fa-key" aria-hidden="true"></span> {{ $t( 'cmd.ChangePassword' ) }}</button>
    </template>
    <FormSection v-bind:title="$t( 'title.Account' )">
      <button type="button" class="btn btn-default" v-on:click="editUser"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
    </FormSection>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.Login' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ login }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.EmailAddress' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ email }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.Language' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ languageName }}</div>
        </div>
      </div>
    </div>
    <FormSection v-bind:title="$t( 'title.GlobalAccess' )">
      <button v-if="!isCurrentUser" type="button" class="btn btn-default" v-on:click="editAccess">
        <span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}
      </button>
    </FormSection>
    <div class="alert alert-default">
      {{ globalAccess }}
    </div>
    <FormSection v-bind:title="$t( 'title.Projects' )">
      <button v-if="isAdministrator" type="button" class="btn btn-success" v-on:click="addProjects">
        <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
      </button>
    </FormSection>
    <Grid v-if="sortedProjects.length > 0" v-bind:items="sortedProjects" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-large', null ]"
          v-bind:row-click-disabled="!isAdministrator" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass, columnKey }">
        <td v-bind:key="columnKey" v-bind:class="columnClass" v-html="getCellValue( columnIndex, item )"></td>
      </template>
    </Grid>
    <div v-else class="alert alert-info">
      {{ isCurrentUser ? $t( 'info.NoOwnProjects' ) : $t( 'info.NoUserProjects' ) }}
    </div>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    userId: Number,
    name: String,
    login: String,
    email: String,
    language: String,
    access: Number,
    userProjects: Array,
    accountMode: Boolean
  },

  computed: {
    ...mapState( 'global', [ 'projects', 'settings', 'languages' ] ),
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    breadcrumbs() {
      return this.accountMode ? null : [
        { label: this.$t( 'title.UserAccounts' ), route: 'ManageUsers' }
      ];
    },
    languageName() {
      if ( this.language == null ) {
        return this.$t( 'text.DefaultLanguage' );
      } else {
        const language = this.languages.find( l => l.key == this.language );
        if ( language != null )
          return language.name;
      }
    },
    globalAccess() {
      if ( this.access == Access.NoAccess )
        return this.$t( 'text.Disabled' );
      else if ( this.access == Access.NormalAccess )
        return this.$t( 'text.RegularUser' );
      else if ( this.access == Access.AdministratorAccess )
        return this.$t( 'text.SystemAdministrator' );
    },
    sortedProjects() {
      return this.projects.map( p => this.userProjects.find( up => up.id == p.id ) ).filter( up => up != null );
    },
    columnNames() {
      return [
        this.$t( 'title.Name' ),
        this.$t( 'title.Access' )
      ];
    },
    isCurrentUser() {
      return this.userId == this.$store.state.global.userId;
    },
    canResetPassword() {
      return this.$store.state.global.settings.resetPassword;
    }
  },

  methods: {
    getCellValue( columnIndex, userProject ) {
      switch ( columnIndex ) {
        case 0:
          const project = this.projects.find( p => p.id == userProject.id );
          if ( project != null )
            return project.name;
        case 1:
          if ( userProject.access == Access.NormalAccess )
            return this.$t( 'text.RegularMember' );
          else if ( userProject.access == Access.AdministratorAccess )
            return this.$t( 'text.ProjectAdministrator' );
          break;
      }
    },

    editUser() {
      this.$router.push( this.accountMode ? 'EditAccount' : 'EditUser', { userId: this.userId } );
    },
    editAccess() {
      this.$router.push( 'EditUserAccess', { userId: this.userId } );
    },
    changePassword() {
      this.$router.push( this.accountMode ? 'ChangeAccountPassword' : 'ChangePassword', { userId: this.userId } );
    },
    resetPassword() {
      this.$router.push( this.accountMode ? 'ResetAccountPassword' : 'ResetPassword', { userId: this.userId } );
    },

    addProjects() {
      this.$router.push( this.accountMode ? 'AddAccountProjects' : 'AddUserProjects', { userId: this.userId } );
    },

    rowClick( rowIndex ) {
      this.$router.push( this.accountMode ? 'EditAccountProject' : 'EditUserProject', { userId: this.userId, projectId: this.sortedProjects[ rowIndex ].id } );
    }
  }
}
</script>
