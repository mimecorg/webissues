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
  <BaseForm v-bind:title="$t( 'cmd.DeleteAlert' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt v-bind:path="promptPath"><strong>{{ view }}</strong><strong>{{ location }}</strong></Prompt>
  </BaseForm>
</template>

<script>
export default {
  props: {
    alertId: Number,
    isPublic: Boolean,
    view: String,
    location: String
  },

  computed: {
    promptPath() {
      if ( this.location == null )
        return this.isPublic ? 'prompt.DeletePublicAlert' : 'prompt.DeletePersonalAlert';
      else
        return this.isPublic ? 'prompt.DeletePublicAlertWithLocation' : 'prompt.DeletePersonalAlertWithLocation';
    }
  },

  methods: {
    submit() {
      this.$form.block();

      const data = { alertId: this.alertId };

      this.$ajax.post( '/alerts/delete.php', data ).then( () => {
        this.$store.commit( 'alerts/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'ManageAlerts' );
    }
  }
}
</script>
