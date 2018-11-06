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
    <FormHeader v-bind:title="$t( 'title.AdvancedSettings' )" v-on:close="close"/>
    <Panel v-bind:title="$t( 'title.ViewSettings' )">
      <FormCheckbox v-bind:label="$t( 'text.HideIDColumn' )" v-model="hideIdColumn"/>
      <FormCheckbox v-bind:label="$t( 'text.HideEmptyAttributes' )" v-model="hideEmptyValues"/>
      <FormDropdown v-bind:label="$t( 'label.OrderOfIssueHistory' )" v-bind:items="orderItems" v-bind:item-names="orderNames" v-model="historyOrder"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.Editing' )">
      <FormDropdown v-bind:label="$t( 'label.DefaultTextFormat' )" v-bind:items="formatItems" v-bind:item-names="formatNames" v-model="defaultFormat"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.Limits' )">
      <FormDropdown v-bind:label="$t( 'label.MaximumTextLength' )" v-bind:items="commentItems" v-bind:item-names="commentNames" v-model="commentMaxLength"/>
      <FormDropdown v-bind:label="$t( 'label.MaximumAttachmentSize' )" v-bind:items="attachmentItems" v-bind:item-names="attachmentNames" v-model="fileMaxSize"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.AttachmentStorage' )">
      <p>{{ $t( 'prompt.AttachmentStorage' ) }}</p>
      <FormDropdown v-bind:label="$t( 'label.DatabaseStorageThreshold' )" v-bind:items="storageItems" v-bind:item-names="storageNames" v-model="fileDbMaxSize"/>
    </Panel>
    <Panel v-bind:title="$t( 'title.MaximumLifetime' )">
      <FormDropdown v-bind:label="$t( 'label.SessionLifetime' )" v-bind:items="sessionItems" v-bind:item-names="sessionNames" v-model="sessionMaxLifetime"/>
      <FormDropdown v-bind:label="$t( 'label.RegistrationRequestLifetime' )" v-bind:items="registerItems" v-bind:item-names="registerNames" v-model="registerMaxLifetime"/>
      <FormDropdown v-bind:label="$t( 'label.EventLogLifetime' )" v-bind:items="logItems" v-bind:item-names="logNames" v-model="logMaxLifetime"/>
    </Panel>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { TextFormat } from '@/constants'

const MaxInt = Math.pow( 2, 31 ) - 1;

export default {
  props: {
    settings: Object
  },

  fields() {
    return {
      hideIdColumn: {
        value: this.settings.hideIdColumn,
        type: Boolean
      },
      hideEmptyValues: {
        value: this.settings.hideEmptyValues,
        type: Boolean
      },
      historyOrder: {
        value: this.settings.historyOrder,
        type: String
      },
      defaultFormat: {
        value: this.settings.defaultFormat,
        type: Number
      },
      commentMaxLength: {
        value: this.settings.commentMaxLength,
        type: Number
      },
      fileMaxSize: {
        value: this.settings.fileMaxSize,
        type: Number
      },
      fileDbMaxSize: {
        value: this.settings.fileDbMaxSize,
        type: Number
      },
      sessionMaxLifetime: {
        value: this.settings.sessionMaxLifetime,
        type: Number
      },
      registerMaxLifetime: {
        value: this.settings.registerMaxLifetime,
        type: Number
      },
      logMaxLifetime: {
        value: this.settings.logMaxLifetime,
        type: Number
      }
    };
  },

  computed: {
    orderItems() {
      return [ 'asc', 'desc' ];
    },
    orderNames() {
      return [ this.$t( 'text.OldestFirst' ), this.$t( 'text.NewestFirst' ) ];
    },
    formatItems() {
      return [ TextFormat.PlainText, TextFormat.TextWithMarkup ];
    },
    formatNames() {
      return [ this.$t( 'text.PlainText' ), this.$t( 'text.TextWithMarkup' ) ];
    },
    commentItems() {
      return [ 1, 2, 5, 10, 20, 50, 100 ].map( n => n * 1000 );
    },
    commentNames() {
      return this.commentItems.map( n => this.$t( 'text.Characters', [ this.$formatter.formatDecimalNumber( n, 0 ) ] ) );
    },
    attachmentItems() {
      return Array( 15 ).fill( 0 ).map( ( x, i ) => Math.pow( 2, i + 14 ) );
    },
    attachmentNames() {
      return this.attachmentItems.map( n => this.$formatter.formatFileSize( n ) );
    },
    storageItems() {
      return [ 0, ...Array( 9 ).fill( 0 ).map( ( x, i ) => Math.pow( 2, i + 10 ) ), MaxInt ];
    },
    storageNames() {
      return this.storageItems.map( n => {
        if ( n == 0 )
          return this.$t( 'text.Never' );
        else if ( n < MaxInt )
          return this.$formatter.formatFileSize( n );
        else
          return this.$t( 'text.Always' );
      } );
    },
    sessionItems() {
      return [ ...[ 10, 20, 30, 40, 50 ].map( n => n * 60 ), ...[ 1, 2, 3, 4, 6, 8, 10, 12, 18, 24 ].map( n => n * 60 * 60 ) ];
    },
    sessionNames() {
      return this.sessionItems.map( n => this.$formatter.formatTimeDiff( n ) );
    },
    registerItems() {
      return [ 2, 4, 6, 12, 18, 24, 36, 48 ].map( n => n * 60 * 60 );
    },
    registerNames() {
      return this.registerItems.map( n => this.$formatter.formatTimeDiff( n ) );
    },
    logItems() {
      return [ 1, 2, 3, 4, 5, 6, 7, 10, 14, 21, 30, 50, 70, 90, 120 ].map( n => n * 24 * 60 * 60 );
    },
    logNames() {
      return this.logItems.map( n => this.$formatter.formatTimeDiff( n ) );
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = {
        hideIdColumn: this.hideIdColumn,
        hideEmptyValues: this.hideEmptyValues,
        historyOrder: this.historyOrder,
        defaultFormat: this.defaultFormat,
        commentMaxLength: this.commentMaxLength,
        fileMaxSize: this.fileMaxSize,
        fileDbMaxSize: this.fileDbMaxSize,
        sessionMaxLifetime: this.sessionMaxLifetime,
        registerMaxLifetime: this.registerMaxLifetime,
        logMaxLifetime: this.logMaxLifetime
      };

      this.$emit( 'block' );

      this.$ajax.post( '/settings/advanced/edit.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ServerSettings' );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
