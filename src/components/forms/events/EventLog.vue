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
    <template v-slot:header>
      <DropdownButton v-bind:btn-class="filter != null ? 'btn-primary' : 'btn-default'" fa-class="fa-filter" menu-class="dropdown-menu-right" v-bind:title="filterTitle">
        <li v-bind:class="{ active: filter == null }">
          <HyperLink v-on:click="setFilter( null )">{{ getFilterText( null ) }}</HyperLink>
        </li>
        <li role="separator" class="divider"></li>
        <li v-for="item in allFilters" v-bind:key="item" v-bind:class="{ active: filter == item }">
          <HyperLink v-on:click="setFilter( item )">{{ getFilterText( item ) }}</HyperLink>
        </li>
      </DropdownButton>
    </template>
    <Grid v-if="events.length > 0" v-bind:items="events" v-bind:columns="columns"
          footer-visible v-bind:previous-enabled="previousEnabled" v-bind:next-enabled="nextEnabled"
          v-bind:status-text="statusText" v-on:previous="previous" v-on:next="next"
          v-on:row-click="rowClick">
      <template v-slot:date-cell="{ item: event }">
        {{ $formatter.formatStamp( event.date ) }}
      </template>
      <template v-slot:type-cell="{ item: event }">
        {{ $t( 'EventType.' + event.type ) }}
      </template>
      <template v-slot:severity-cell="{ item: event }">
        <span v-bind:class="[ 'fa', 'fa-fw', getIcon( event ) ]" aria-hidden="true"></span> {{ getSeverity( event ) }}
      </template>
    </Grid>
    <Prompt v-else path="info.NoEvents"/>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { EventType, EventSeverity } from '@/constants'

export default {
  computed: {
    ...mapState( 'events', [ 'filter', 'events', 'totalCount' ] ),
    ...mapGetters( 'events', [ 'firstIndex', 'lastIndex' ] ),
    columns() {
      return {
        message: { title: this.$t( 'title.Message' ), class: 'column-large' },
        date: { title: this.$t( 'title.Date' ) },
        type: { title: this.$t( 'title.Type' ) },
        severity: { title: this.$t( 'title.Severity' ) }
      };
    },
    previousEnabled() {
      return this.firstIndex > 1;
    },
    nextEnabled() {
      return this.lastIndex < this.totalCount;
    },
    statusText() {
      if ( this.firstIndex == 1 && this.lastIndex == this.totalCount )
        return this.$t( 'text.EventsCount', [ this.totalCount ] );
      else
        return this.$t( 'text.EventsCountOf', [ this.firstIndex, this.lastIndex, this.totalCount ] );
    },
    allFilters() {
      return [ EventType.Errors, EventType.Access, EventType.Audit, EventType.Cron ];
    },
    filterTitle() {
      if ( this.filter != null )
        return this.$t( 'text.Filter', [ this.getFilterText( this.filter ) ] );
      else
        return this.$t( 'title.Filter' );
    }
  },

  methods: {
    getSeverity( event ) {
      if ( event.severity == EventSeverity.Information )
        return this.$t( 'text.Information' );
      else if ( event.severity == EventSeverity.Warning )
        return this.$t( 'text.Warning' );
      else if ( event.severity == EventSeverity.Error )
        return this.$t( 'text.Error' );
    },

    getIcon( event ) {
      if ( event.severity == EventSeverity.Information )
        return 'fa-info-circle text-info';
      else if ( event.severity == EventSeverity.Warning )
        return 'fa-exclamation-triangle text-warning';
      else if ( event.severity == EventSeverity.Error )
        return 'fa-exclamation-circle text-danger';
    },

    getFilterText( filter ) {
      if ( filter != null )
        return this.$t( 'EventType.' + filter );
      else
        return this.$t( 'text.AllEvents' );
    },

    setFilter( filter ) {
      this.$store.commit( 'events/setFilter', { filter } );
      this.update();
    },
    previous() {
      this.$store.commit( 'events/setPreviousPage' );
      this.update();
    },
    next() {
      this.$store.commit( 'events/setNextPage' );
      this.update();
    },
    update() {
      this.$form.block();
      this.$store.dispatch( 'events/load' ).then( () => {
        this.$form.unblock();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    rowClick( rowIndex ) {
      this.$router.push( 'EventDetails', { eventId: this.events[ rowIndex ].id } );
    }
  }
}
</script>
