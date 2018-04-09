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
    <Prompt v-if="mode == 'edit'" path="EditProjectDescription.EditDescriptionPrompt"><strong>{{ projectName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditProjectDescription.AddDescriptionPrompt"><strong>{{ projectName }}</strong></Prompt>
    <MarkupEditor ref="description" id="description" v-bind:label="$t( 'EditProjectDescription.Description' )" v-bind="$field( 'description' )"
                  v-bind:format="descriptionFormat" v-model="description" v-on:select-format="selectFormat" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  props: {
    mode: String,
    projectId: Number,
    projectName: String,
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
        return this.$t( 'EditProjectDescription.EditDescription' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditProjectDescription.AddDescription' );
    }
  },

  methods: {
    selectFormat( format ) {
      this.descriptionFormat = format;
    },

    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = { projectId: this.projectId, description: this.description, descriptionFormat: this.descriptionFormat };

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/projects/description/' + this.mode + '.php', data ).then( () => {
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ProjectDetails', { projectId: this.projectId } );
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
