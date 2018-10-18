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
    <Prompt v-if="mode == 'edit'" path="prompt.EditComment"><strong>{{ '#' + commentId }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddComment"><strong>{{ issueName }}</strong></Prompt>
    <MarkupEditor ref="comment" id="comment" v-bind:label="$t( 'label.Comment' )" v-bind="$field( 'comment' )"
                  v-bind:format.sync="commentFormat" v-model="comment" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  props: {
    mode: String,
    issueId: Number,
    issueName: String,
    commentId: Number,
    initialComment: String,
    initialFormat: Number
  },

  fields() {
    return {
      comment: {
        value: this.initialComment,
        type: String,
        required: true,
        maxLength: this.$store.state.global.settings.commentMaxLength,
        multiLine: true
      }
    };
  },

  data() {
    return {
      commentFormat: this.initialFormat
    };
  },

  computed: {
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.EditComment' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddComment' );
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() && this.commentFormat == this.initialFormat ) {
        this.returnToDetails( this.commentId );
        return;
      }

      const data = {};
      if ( this.mode == 'add' )
        data.issueId = this.issueId;
      else
        data.commentId = this.commentId;
      data.comment = this.comment;
      data.commentFormat = this.commentFormat;

      this.$emit( 'block' );

      this.$ajax.post( '/issues/comments/' + this.mode + '.php', data ).then( ( { stampId } ) => {
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
    this.$refs.comment.focus();
  }
}
</script>
