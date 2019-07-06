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
  <BaseForm v-bind:title="title" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt v-bind:path="promptPath"/>
    <FormGroup v-bind:label="$t( 'label.Filter' )" v-bind="$field( 'typeId' )">
      <ViewFilters ref="typeId" v-bind:typeId.sync="typeId" v-bind:viewId.sync="viewId" v-bind:show-personal="!isPublic"/>
    </FormGroup>
    <FormGroup v-bind:label="$t( 'label.Location' )">
      <LocationFilters v-bind:typeId="typeId != null ? typeId : -1" v-bind:projectId.sync="projectId" v-bind:folderId.sync="folderId"
                       v-bind:project-label="$t( 'text.AllProjects' )" v-bind:folder-label="$t( 'text.AllFolders' )" folder-visible/>
    </FormGroup>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

import { ErrorCode, Reason } from '@/constants'

export default {
  props: {
    mode: String,
    isPublic: Boolean
  },

  fields() {
    return {
      typeId: {
        value: null,
        type: Number,
        required: true,
        requiredError: this.$t( 'error.NoTypeSelected' )
      }
    };
  },

  data() {
    return {
      viewId: null,
      projectId: null,
      folderId: null
    }
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] ),
    title() {
      return this.isPublic ? this.$t( 'cmd.AddPublicAlert' ) : this.$t( 'cmd.AddPersonalAlert' );
    },
    promptPath() {
      return this.isPublic ? 'prompt.AddPublicAlert' : 'prompt.AddPersonalAlert';
    }
  },

  watch: {
    typeId( value ) {
      if ( this.folderId != null ) {
        const project = this.projects.find( p => p.id == this.projectId );
        const folder = project != null ? project.folders.find( f => f.id == this.folderId ) : null;
        if ( folder == null || folder.typeId != value )
          this.folderId = null;
      }
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      const data = {};
      data.typeId = this.typeId;
      data.viewId = this.viewId;
      data.projectId = this.folderId == null ? this.projectId : null;
      data.folderId = this.folderId;
      data.isPublic = this.isPublic;

      this.$form.block();

      this.$ajax.post( '/alerts/add.php', data ).then( ( { alertId, changed } ) => {
        if ( changed )
          this.$store.commit( 'alerts/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.AlertAlreadyExists ) {
          this.$form.unblock();
          this.typeIdError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.typeId.focus();
          } );
        } else {
          this.$form.error( error );
        }
      } );
    },

    returnToDetails() {
      this.$router.push( 'ManageAlerts' );
    }
  }
}
</script>
