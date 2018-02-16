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
    <FormHeader v-bind:title="title" v-on:close="close"/>
    <Prompt v-if="mode == 'edit'" path="EditDescription.EditDescriptionPrompt"><strong>{{ issueName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditDescription.AddDescriptionPrompt"><strong>{{ issueName }}</strong></Prompt>
    <MarkupEditor ref="description" id="description" v-bind:label="$t( 'EditDescription.Description' )" v-bind:required="true" v-bind:error="descriptionError"
                  v-bind:format="selectedFormat" v-model="descriptionValue" v-on:select-format="selectFormat" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    mode: String,
    issueId: Number,
    issueName: String,
    description: String,
    descriptionFormat: Number
  },

  data() {
    return {
      descriptionValue: this.description,
      selectedFormat: this.descriptionFormat,
      descriptionError: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'settings' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditDescription.EditDescription' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditDescription.AddDescription' );
    }
  },

  methods: {
    selectFormat( format ) {
      this.selectedFormat = format;
    },

    submit() {
      this.descriptionError = null;

      const data = { issueId: this.issueId };
      let modified = false;
      let valid = true;

      try {
        this.descriptionValue = this.$parser.normalizeString( this.descriptionValue, this.settings.commentMaxLength, { allowEmpty: false, multiLine: true } );
        if ( this.mode == 'add' || this.descriptionValue != this.description ) {
          modified = true;
          data.description = this.descriptionValue;
          data.descriptionFormat = this.selectedFormat;
        }
      } catch ( error ) {
        if ( error.reason == 'APIError' ) {
          this.descriptionError = this.$t( 'ErrorCode.' + error.errorCode );
          if ( valid )
            this.$refs.description.focus();
          valid = false;
        } else {
          throw error;
        }
      }

      if ( !valid )
        return;

      if ( this.mode == 'edit' && !modified ) {
        this.returnToDetails();
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/description/' + this.mode + '.php', data ).then( ( { stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    },

    close() {
      this.$emit( 'close' );
    },
    error( error ) {
      this.$emit( 'error', error );
    }
  },

  mounted() {
    this.$refs.description.focus();
  }
}
</script>
