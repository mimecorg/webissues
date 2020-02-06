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
  <BaseForm v-bind:title="view" v-bind:breadcrumbs="breadcrumbs" auto-close save-position>
    <template v-slot:header>
      <button type="button" class="btn btn-default" v-on:click="deleteAlert"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}</button>
    </template>
    <FormSection v-bind:title="$t( 'title.Details' )"/>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-3 col-sm-2">{{ $t( 'label.Filter' ) }}</div>
          <div class="col-xs-9 col-sm-10">{{ view }}</div>
        </div>
        <div class="row">
          <div class="col-xs-3 col-sm-2">{{ $t( 'label.Location' ) }}</div>
          <div class="col-xs-9 col-sm-10">{{ location }}</div>
        </div>
      </div>
    </div>
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
    breadcrumbs() {
      return [
        { label: this.$t( 'title.Alerts' ), route: 'ManageAlerts' }
      ];
    }
  },

  methods: {
    deleteAlert() {
      this.$router.push( 'DeleteAlert', { alertId: this.alertId } );
    }
  }
}
</script>
