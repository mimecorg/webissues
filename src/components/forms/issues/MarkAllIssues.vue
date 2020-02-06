<!--
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
  <BaseForm v-bind:title="title" size="small" with-buttons v-bind:cancel-hidden="type == null" v-on:ok="submit" v-on:cancel="cancel">
    <Prompt v-if="type != null" path="prompt.MarkAllIssues"><strong>{{ totalCount }}</strong><template>{{ read ? $t( 'text.MarkAsRead' ) : $t( 'text.MarkAsUnread' ) }}</template></Prompt>
    <Prompt v-else alert-class="alert-warning" path="prompt.NoTypeSelected"/>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
  props: {
    read: Boolean
  },

  computed: {
    ...mapState( 'list', [ 'totalCount' ] ),
    ...mapGetters( 'list', [ 'type' ] ),
    title() {
      if ( this.read )
        return this.$t( 'cmd.MarkAllAsRead' );
      else
        return this.$t( 'cmd.MarkAllAsUnread' );
    }
  },

  methods: {
    submit() {
      if ( this.type == null ) {
        this.$form.close();
        return;
      }

      this.$form.block();

      this.$store.dispatch( 'list/markAll', { read: this.read } ).then( () => {
        this.$store.commit( 'list/setDirty' );
        this.$store.commit( 'alerts/setDirty' );
        this.$form.close();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    cancel() {
      this.$form.close();
    }
  }
}
</script>
