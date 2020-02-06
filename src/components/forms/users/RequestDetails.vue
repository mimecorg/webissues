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
  <BaseForm v-bind:title="name" v-bind:breadcrumbs="breadcrumbs" auto-close save-position>
    <template v-slot:header>
      <button type="button" class="btn btn-primary" v-on:click="approveRequest"><span class="fa fa-check" aria-hidden="true"></span> {{ $t( 'cmd.Approve' ) }}</button>
      <button type="button" class="btn btn-default" v-on:click="rejectRequest"><span class="fa fa-ban" aria-hidden="true"></span> {{ $t( 'cmd.Reject' ) }}</button>
    </template>
    <FormSection v-bind:title="$t( 'title.Details' )"/>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.Login' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ login }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.EmailAddress' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ email }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-3">{{ $t( 'label.CreatedDate' ) }}</div>
          <div class="col-xs-8 col-sm-9">{{ formattedDate }}</div>
        </div>
      </div>
    </div>
  </BaseForm>
</template>

<script>
export default {
  props: {
    requestId: Number,
    name: String,
    login: String,
    email: String,
    date: Number
  },

  computed: {
    breadcrumbs() {
      return [
        { label: this.$t( 'title.UserAccounts' ), route: 'ManageUsers' },
        { label: this.$t( 'title.RegistrationRequests' ), route: 'RegistrationRequests' }
      ];
    },
    formattedDate() {
      return this.$formatter.formatStamp( this.date );
    }
  },

  methods: {
    approveRequest() {
      this.$router.push( 'ApproveRequest', { requestId: this.requestId } );
    },
    rejectRequest() {
      this.$router.push( 'RejectRequest', { requestId: this.requestId } );
    }
  }
}
</script>
