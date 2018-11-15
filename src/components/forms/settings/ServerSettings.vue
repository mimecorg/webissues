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
  <BaseForm v-bind:title="$t( 'title.ServerSettings' )" auto-close save-position>

    <FormSection v-bind:title="$t( 'title.ServerInformation' )">
      <DropdownButton fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'title.More' )">
        <li><HyperLink v-on:click="renameServer"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.RenameServer' ) }}</HyperLink></li>
        <li><HyperLink v-on:click="resetUuid"><span class="fa fa-refresh" aria-hidden="true"></span> {{ $t( 'cmd.ResetUniqueID' ) }}</HyperLink></li>
      </DropdownButton>
    </FormSection>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.ServerName' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ serverName }}</div>
        </div>
      </div>
    </div>

    <FormSection v-bind:title="$t( 'title.RegionalSettings' )">
      <button type="button" class="btn btn-default" v-on:click="regionalSettings"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
    </FormSection>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.Language' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ languageName }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.TimeZone' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ timeZoneName }}</div>
        </div>
      </div>
    </div>

    <FormSection v-bind:title="$t( 'title.EmailSettings' )">
      <button type="button" class="btn btn-default" v-on:click="emailSettings"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
    </FormSection>
    <div v-if="hasEmail" class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.EmailAddress' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ settings.emailFrom }} </div>
        </div>
      </div>
    </div>
    <Prompt v-else path="prompt.WarningEmailsDisabled" alert-class="alert-warning"><strong>{{ $t( 'label.Warning' ) }}</strong></Prompt>

    <FormSection v-bind:title="$t( 'title.CronJob' )"/>
    <Prompt v-if="hasCronWarning && cronLastRun != null" path="prompt.WarningCronJobStarted" alert-class="alert-warning">
      <strong>{{ $t( 'label.Warning' ) }}</strong>{{ cronLastRun }}
    </Prompt>
    <Prompt v-else-if="hasCronWarning" path="prompt.WarningCronJobNotStarted" alert-class="alert-warning"><strong>{{ $t( 'label.Warning' ) }}</strong></Prompt>
    <Prompt v-else-if="cronLastRun != null" path="prompt.CronJobStarted" alert-class="alert-default">{{ cronLastRun }}</Prompt>
    <Prompt v-else path="prompt.CronJobNotStarted" alert-class="alert-default"/>

    <FormSection v-bind:title="$t( 'title.AccessSettings' )">
      <button type="button" class="btn btn-default" v-on:click="accessSettings"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
    </FormSection>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.AnonymousAccess' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ anonymousAccessEnabled }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.UserRegistration' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ selfRegisterEnabled }}</div>
        </div>
      </div>
    </div>

    <FormSection v-bind:title="$t( 'title.EmailInboxes' )">
      <button v-if="hasImap" type="button" class="btn btn-default" v-on:click="addInbox"><span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}</button>
    </FormSection>
    <Grid v-if="hasImap && inboxes.length > 0" v-bind:items="inboxes" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-large', null ]" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass, columnKey }">
        <td v-bind:key="columnKey" v-bind:class="columnClass" v-html="getCellValue( columnIndex, item )"></td>
      </template>
    </Grid>
    <Prompt v-else-if="hasImap" path="info.NoEmailInboxes" alert-class="alert-default"/>
    <Prompt v-else path="prompt.EmailInboxesNotAvailable" alert-class="alert-default"/>

    <FormSection v-bind:title="$t( 'title.AdvancedSettings' )">
      <button type="button" class="btn btn-default" v-on:click="advancedSettings"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'cmd.Edit' ) }}</button>
    </FormSection>
    <Prompt path="prompt.AdvancedSettings" alert-class="alert-default"/>

  </BaseForm>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    serverName: String,
    settings: Object,
    inboxes: Array,
    hasImap: Boolean
  },

  computed: {
    ...mapState( 'global', [ 'languages' ] ),
    hasEmail() {
      return this.settings.emailEngine != null;
    },
    hasCronWarning() {
      return this.hasEmail && ( this.settings.cronLast == null || this.settings.cronLast > 7200 );
    },
    cronLastRun() {
      if ( this.settings.cronLast != null )
        return this.$formatter.formatTimeDiff( this.settings.cronLast );
      else
        return null;
    },
    anonymousAccessEnabled() {
      if ( this.settings.anonymousAccess )
        return this.$t( 'text.Enabled' );
      else
        return this.$t( 'text.Disabled' );
    },
    selfRegisterEnabled() {
      if ( !this.hasEmail )
        return this.$t( 'text.NotAvailable' );
      else if ( this.settings.selfRegister )
        return this.$t( 'text.Enabled' );
      else
        return this.$t( 'text.Disabled' );
    },
    languageName() {
      const language = this.languages.find( l => l.key == this.settings.language );
      if ( language != null )
        return language.name;
    },
    timeZoneName() {
      return this.settings.timeZone.replace( /_/g, ' ' ).replace( /\//g, ' / ' ).replace( /St /g, 'St. ' );
    },
    columnNames() {
      return [
        this.$t( 'title.Email' ),
        this.$t( 'title.Type' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, inbox ) {
      switch ( columnIndex ) {
        case 0:
          return inbox.email;
        case 1:
          if ( inbox.engine == 'imap' )
            return this.$t( 'text.IMAP' );
          else if ( inbox.engine == 'pop3' )
            return this.$t( 'text.POP3' );
          break;
      }
    },

    renameServer() {
      this.$router.push( 'RenameServer' );
    },
    resetUuid() {
      this.$router.push( 'ResetUuid' );
    },

    emailSettings() {
      this.$router.push( 'EmailSettings' );
    },
    accessSettings() {
      this.$router.push( 'AccessSettings' );
    },
    regionalSettings() {
      this.$router.push( 'RegionalSettings' );
    },
    advancedSettings() {
      this.$router.push( 'AdvancedSettings' );
    },

    addInbox() {
      this.$router.push( 'AddInbox' );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'EditInbox', { inboxId: this.inboxes[ rowIndex ].id } );
    }
  }
}
</script>
