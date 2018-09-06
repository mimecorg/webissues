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
    <MarkupEditor ref="description" id="description" v-bind:label="$t( 'EditDescription.Description' )" v-bind="$field( 'description' )"
                  v-bind:format.sync="descriptionFormat" v-model="description" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  props: {
    mode: String,
    issueId: Number,
    issueName: String,
    initialDescription: String,
    initialFormat: Number
  },

  fields() {
    return {
      description: {
        value: this.initialDescription,
        type: String,
        required: true,
        maxLength: this.$store.state.global.settings.commentMaxLength,
        multiLine: true
      }
    };
  },

  data() {
    return {
      descriptionFormat: this.initialFormat
    };
  },

  computed: {
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditDescription.EditDescription' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditDescription.AddDescription' );
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() && this.descriptionFormat == this.initialFormat ) {
        this.returnToDetails();
        return;
      }

      const data = { issueId: this.issueId, description: this.description, descriptionFormat: this.descriptionFormat };

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issues/description/' + this.mode + '.php', data ).then( ( { stampId } ) => {
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
