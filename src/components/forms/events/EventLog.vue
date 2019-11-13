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
  <BaseForm v-bind:title="$t( 'title.EventLog' )" size="large" auto-close save-position>
    <Grid v-if="events.length > 0" v-bind:items="events" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-large', null, null, null ]" v-on:row-click="rowClick">
      <template slot-scope="{ item, columnIndex, columnClass, columnKey }">
        <td v-bind:key="columnKey" v-bind:class="columnClass">
          <span v-if="columnIndex == 3" v-bind:class="[ 'fa', 'fa-fw', getIcon( item ) ]" aria-hidden="true"></span> {{ getCellValue( columnIndex, item ) }}
        </td>
      </template>
    </Grid>
    <Prompt v-else path="info.NoEvents"/>
  </BaseForm>
</template>

<script>
import { EventSeverity } from '@/constants'

export default {
  props: {
    events: Array
  },

  computed: {
    columnNames() {
      return [
        this.$t( 'title.Message' ),
        this.$t( 'title.Date' ),
        this.$t( 'title.Type' ),
        this.$t( 'title.Severity' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, event ) {
      switch ( columnIndex ) {
        case 0:
          return event.message;
        case 1:
          return this.$formatter.formatStamp( event.date );
        case 2:
          return this.$t( 'EventType.' + event.type );
        case 3:
          if ( event.severity == EventSeverity.Information )
            return this.$t( 'text.Information' );
          else if ( event.severity == EventSeverity.Warning )
            return this.$t( 'text.Warning' );
          else if ( event.severity == EventSeverity.Error )
            return this.$t( 'text.Error' );
          break;
      }
    },

    getIcon( event ) {
      if ( event.severity == EventSeverity.Information )
        return 'fa-info-circle text-info';
      else if ( event.severity == EventSeverity.Warning )
        return 'fa-exclamation-triangle text-warning';
      else if ( event.severity == EventSeverity.Error )
        return 'fa-exclamation-circle text-danger';
    },

    rowClick( rowIndex ) {
      this.$router.push( 'EventDetails', { eventId: this.events[ rowIndex ].id } );
    }
  }
}
</script>
