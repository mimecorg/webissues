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
    <Prompt v-if="mode == 'edit'" path="prompt.EditDescription"><strong>{{ issueName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddDescription"><strong>{{ issueName }}</strong></Prompt>
    <MarkupEditor ref="description" id="description" v-bind:label="$t( 'label.Description' )" v-bind="$field( 'description' )"
                  v-bind:format.sync="descriptionFormat" v-model="description"/>
  </BaseForm>
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
      },
      descriptionFormat: {
        value: this.initialFormat,
        type: Number
      }
    };
  },

  computed: {
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditDescription' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddDescription' );
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

      const data = { issueId: this.issueId, description: this.description, descriptionFormat: this.descriptionFormat };

      this.$form.block();

      this.$ajax.post( '/issues/description/' + this.mode + '.php', data ).then( ( { stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    }
  },

  mounted() {
    this.$refs.description.focus();
  }
}
</script>
