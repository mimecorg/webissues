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
  <BaseForm v-bind:title="$t( 'title.EventDetails' )" v-bind:breadcrumbs="breadcrumbs" size="large" auto-close>
    <div class="panel panel-default">
      <div class="panel-body panel-table">
        <div class="row">
          <div class="col-xs-4 col-sm-2">{{ $t( 'label.Date' ) }}</div>
          <div class="col-xs-8 col-sm-10">{{ date }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-2">{{ $t( 'label.Type' ) }}</div>
          <div class="col-xs-8 col-sm-10">{{ type }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-2">{{ $t( 'label.Severity' ) }}</div>
          <div class="col-xs-8 col-sm-10"><span v-bind:class="[ 'fa', 'fa-fw', icon ]" aria-hidden="true"></span> {{ severity }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-2">{{ $t( 'label.Message' ) }}</div>
          <div class="col-xs-8 col-sm-10 panel-multiline">{{ details.message }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-2">{{ $t( 'label.UserName' ) }}</div>
          <div class="col-xs-8 col-sm-10">{{ details.user }}</div>
        </div>
        <div class="row">
          <div class="col-xs-4 col-sm-2">{{ $t( 'label.HostName' ) }}</div>
          <div class="col-xs-8 col-sm-10">{{ details.host }}</div>
        </div>
      </div>
    </div>
  </BaseForm>
</template>

<script>
import { EventSeverity } from '@/constants'

export default {
  props: {
    eventId: Number,
    details: Object
  },

  computed: {
    breadcrumbs() {
      return [
        { label: this.$t( 'title.EventLog' ), route: 'EventLog' }
      ];
    },
    date() {
      return this.$formatter.formatStamp( this.details.date );
    },
    type() {
      return this.$t( 'EventType.' + this.details.type )
    },
    severity() {
      if ( this.details.severity == EventSeverity.Information )
        return this.$t( 'text.Information' );
      else if ( this.details.severity == EventSeverity.Warning )
        return this.$t( 'text.Warning' );
      else if ( this.details.severity == EventSeverity.Error )
        return this.$t( 'text.Error' );
    },
    icon() {
      if ( this.details.severity == EventSeverity.Information )
        return 'fa-info-circle text-info';
      else if ( this.details.severity == EventSeverity.Warning )
        return 'fa-exclamation-triangle text-warning';
      else if ( this.details.severity == EventSeverity.Error )
        return 'fa-exclamation-circle text-danger';
    }
  }
}
</script>
