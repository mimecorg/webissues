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
  <BaseForm v-bind:title="$t( 'title.CronSettings' )" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Panel v-bind:title="$t( 'title.ReportSchedule' )">
      <FormDropdown v-bind:label="$t( 'label.ReportHour' )" v-bind:items="hourItems" v-bind:item-names="hourNames" v-model="reportHour"/>
      <FormDropdown v-bind:label="$t( 'label.WeeklyReportDay' )" v-bind:items="dayItems" v-bind:item-names="dayNames" v-model="reportDay"/>
    </Panel>
  </BaseForm>
</template>

<script>
export default {
  props: {
    settings: Object
  },

  fields() {
    return {
      reportHour: {
        value: this.settings.reportHour,
        type: Number
      },
      reportDay: {
        value: this.settings.reportDay,
        type: Number
      }
    };
  },

  computed: {
    hourItems() {
      return Array( 24 ).fill( 0 ).map( ( x, i ) => i );
    },
    hourNames() {
      return this.hourItems.map( n => this.$formatter.formatHour( n ) );
    },
    dayItems() {
      return Array( 7 ).fill( 0 ).map( ( x, i ) => i );
    },
    dayNames() {
      return [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ].map( d => this.$t( 'calendar.weekday.' + d ) );
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( !this.$fields.modified() ) {
        this.returnToDetails();
        return;
      }

      const data = {
        reportHour: this.reportHour,
        reportDay: this.reportDay
      };

      this.$form.block();

      this.$ajax.post( '/settings/cron/edit.php', data ).then( ( { changed } ) => {
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'ServerSettings' );
    }
  }
}
</script>
