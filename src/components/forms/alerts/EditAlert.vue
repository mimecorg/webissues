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
    <template slot="header">
      <button type="button" class="btn btn-default" v-on:click="deleteAlert"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}</button>
    </template>
    <Prompt v-if="mode == 'add'" v-bind:path="promptPath"/>
    <Prompt v-else v-bind:path="promptPath"><strong v-html="view"></strong><strong v-html="location"></strong></Prompt>
    <FormGroup v-if="mode == 'add'" v-bind:label="$t( 'label.Filter' )" v-bind="$field( 'typeId' )">
      <ViewFilters ref="typeId" v-bind:typeId.sync="typeId" v-bind:viewId.sync="viewId" v-bind:show-personal="!isPublic"/>
    </FormGroup>
    <FormGroup v-if="mode == 'add'" v-bind:label="$t( 'label.Location' )">
      <LocationFilters v-bind:typeId="typeId != null ? typeId : -1" v-bind:projectId.sync="projectId" v-bind:folderId.sync="folderId"
                       v-bind:project-label="$t( 'text.AllProjects' )" v-bind:folder-label="$t( 'text.AllFolders' )" folder-visible/>
    </FormGroup>
    <FormGroup v-bind:label="$t( 'label.Type' )" required>
      <div class="radio">
        <label><input type="radio" v-model="type" v-bind:value="alertType.notification"> {{ $t( 'text.Notification' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="type" v-bind:value="alertType.changeReport"> {{ $t( 'text.ChangeReport' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="type" v-bind:value="alertType.issueReport"> {{ $t( 'text.IssueReport' ) }}</label>
      </div>
    </FormGroup>
    <FormGroup v-if="type != alertType.notification" v-bind:label="$t( 'label.Frequency' )" required>
      <div class="radio">
        <label><input type="radio" v-model="frequency" v-bind:value="alertFrequency.daily"> {{ $t( 'text.Daily' ) }}</label>
      </div>
      <div class="radio">
        <label><input type="radio" v-model="frequency" v-bind:value="alertFrequency.weekly"> {{ $t( 'text.Weekly' ) }}</label>
      </div>
    </FormGroup>
  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

import { AlertType, AlertFrequency, ErrorCode, Reason } from '@/constants'

export default {
  props: {
    mode: String,
    alertId: Number,
    isPublic: Boolean,
    view: String,
    location: String,
    initialAlert: Object
  },

  fields() {
    return {
      typeId: {
        value: null,
        type: Number,
        required: true,
        requiredError: this.$t( 'error.NoTypeSelected' ),
        condition: this.mode == 'add'
      },
      type: {
        value: ( this.initialAlert != null ) ? this.initialAlert.type : AlertType.Notification,
        type: Number
      },
      frequency: {
        value: ( this.initialAlert != null && this.initialAlert.type != AlertType.Notification ) ? this.initialAlert.frequency : AlertFrequency.Daily,
        type: Number
      }
    };
  },

  data() {
    return {
      viewId: null,
      projectId: null,
      folderId: null,
      alertType: {
        notification: AlertType.Notification,
        changeReport: AlertType.ChangeReport,
        issueReport: AlertType.IssueReport
      },
      alertFrequency: {
        daily: AlertFrequency.Daily,
        weekly: AlertFrequency.Weekly,
      }
    }
  },

  computed: {
    ...mapState( 'global', [ 'projects' ] ),
    title() {
      if ( this.mode == 'add' )
        return this.isPublic ? this.$t( 'cmd.AddPublicAlert' ) : this.$t( 'cmd.AddPersonalAlert' );
      else
        return this.$t( 'cmd.EditAlert' );
    },
    promptPath() {
      if ( this.mode == 'add' )
        return this.isPublic ? 'prompt.AddPublicAlert' : 'prompt.AddPersonalAlert';
      else if ( this.location == null )
        return this.isPublic ? 'prompt.EditPublicAlert' : 'prompt.EditPersonalAlert';
      else
        return this.isPublic ? 'prompt.EditPublicAlertWithLocation' : 'prompt.EditPersonalAlertWithLocation';
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

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = {};
      if ( this.mode == 'add' ) {
        data.typeId = this.typeId;
        data.viewId = this.viewId;
        data.projectId = this.folderId == null ? this.projectId : null;
        data.folderId = this.folderId;
        data.isPublic = this.isPublic;
      } else {
        data.alertId = this.alertId;
      }
      data.alertType = this.type;
      data.alertFrequency = this.type != AlertType.Notification ? this.frequency : null;

      this.$form.block();

      this.$ajax.post( '/alerts/' + this.mode + '.php', data ).then( ( { alertId, changed } ) => {
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
    },

    deleteAlert() {
      this.$router.push( 'DeleteAlert', { alertId: this.alertId } );
    }
  }
}
</script>
