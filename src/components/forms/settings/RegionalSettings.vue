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
    <FormHeader v-bind:title="$t( 'title.RegionalSettings' )" v-on:close="close"/>
    <FormDropdown v-bind:label="$t( 'label.Language' )" v-bind:items="languageItems" v-bind:item-names="languageNames" v-model="language"/>
    <p>{{ $t( 'prompt.TimeZone' ) }}</p>
    <FormGroup v-bind:label="$t( 'label.TimeZone' )">
      <div class="dropdown-select">
        <DropdownButton ref="dropdown" v-bind:text="timeZoneName" v-on:open="dropdownOpen">
          <div class="dropdown-menu-filter">
            <input ref="filter" type="text" class="form-control" v-model="filter">
          </div>
          <div class="dropdown-menu-scroll">
            <li v-bind:class="{ active: timeZone == '' }">
              <HyperLink v-on:click="timeZone = ''">{{ defaultTimeZoneName }}</HyperLink>
            </li>
            <template v-for="( zone, index ) in filteredTimeZones">
              <li v-bind:key="'sep' + index" role="separator" class="divider"></li>
              <li v-bind:key="'h' + index" class="dropdown-header">{{ zone.offset }}</li>
              <li v-for="name in zone.names" v-bind:key="name" v-bind:class="{ active: name == timeZone }">
                <HyperLink v-on:click="timeZone = name">{{ convertTimeZoneName( name ) }}</HyperLink>
              </li>
            </template>
          </div>
        </DropdownButton>
      </div>
    </FormGroup>
    <Panel v-bind:title="$t( 'title.Formats' )">
      <p>{{ $t( 'prompt.CustomizeFormats' ) }}</p>
      <FormDropdown v-bind:label="$t( 'label.NumberFormat' )" v-bind:items="getFormatItems( 'number' )" v-bind:item-names="getFormatNames( 'number' )"
                    v-bind:default-name="$t( 'text.DefaultFormat' )" v-model="numberFormat"/>
      <FormDropdown v-bind:label="$t( 'label.DateFormat' )" v-bind:items="getFormatItems( 'date' )" v-bind:item-names="getFormatNames( 'date' )"
                    v-bind:default-name="$t( 'text.DefaultFormat' )" v-model="dateFormat"/>
      <FormDropdown v-bind:label="$t( 'label.TimeFormat' )" v-bind:items="getFormatItems( 'time' )" v-bind:item-names="getFormatNames( 'time' )"
                    v-bind:default-name="$t( 'text.DefaultFormat' )" v-model="timeFormat"/>
      <FormDropdown v-bind:label="$t( 'label.FirstDayOfWeek' )" v-bind:items="weekdayItems" v-bind:item-names="weekdayNames"
                    v-bind:default-name="$t( 'text.DefaultFormat' )" v-model="firstDay"/>
    </Panel>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    settings: Object,
    defaultTimeZone: String,
    timeZones: Array,
    formats: Object
  },

  fields() {
    return {
      language: {
        value: this.settings.language,
        type: String
      },
      timeZone: {
        value: this.settings.timeZone,
        type: String
      },
      numberFormat: {
        value: this.settings.numberFormat,
        type: String
      },
      dateFormat: {
        value: this.settings.dateFormat,
        type: String
      },
      timeFormat: {
        value: this.settings.timeFormat,
        type: String
      },
      firstDay: {
        value: this.settings.firstDay,
        type: Number
      }
    };
  },

  data() {
    return {
      filter: ''
    };
  },

  computed: {
    ...mapState( 'global', [ 'languages' ] ),
    languageItems() {
      return this.languages.map( l => l.key );
    },
    languageNames() {
      return this.languages.map( l => l.name );
    },
    defaultTimeZoneName() {
      return this.$t( 'text.DefaultTimeZone', [ this.convertTimeZoneName( this.defaultTimeZone ) ] );
    },
    timeZoneName() {
      if ( this.timeZone != '' )
        return this.convertTimeZoneName( this.timeZone );
      else
        return this.defaultTimeZoneName;
    },
    filteredTimeZones() {
      if ( this.filter == '' ) {
        return this.timeZones;
      } else {
        const filter = this.filter.toUpperCase();
        return this.timeZones.map( zone => ( {
          offset: zone.offset,
          names: zone.names.filter( name => this.convertTimeZoneName( name ).toUpperCase().includes( filter ) )
        } ) ).filter( zone => zone.names.length > 0 );
      }
    },
    weekdayItems() {
      return Array( 7 ).fill( 0 ).map( ( x, i ) => i );
    },
    weekdayNames() {
      return [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ].map( d => this.$t( 'calendar.weekday.' + d ) );
    },
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
        language: this.language,
        timeZone: this.timeZone,
        numberFormat: this.numberFormat,
        dateFormat: this.dateFormat,
        timeFormat: this.timeFormat,
        firstDay: this.firstDay
      };

      this.$emit( 'block' );

      this.$ajax.post( '/settings/regional/edit.php', data ).then( ( { changed, updateLanguage } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        if ( updateLanguage ) {
          this.$i18n.setLocale( this.language ).then( () => {
            this.returnToDetails();
          } );
        } else {
          this.returnToDetails();
        }
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    dropdownOpen() {
      this.$nextTick( () => {
        this.$refs.filter.focus();
      } );
    },

    convertTimeZoneName( name ) {
      return name.replace( /_/g, ' ' ).replace( /\//g, ' / ' ).replace( /St /g, 'St. ' );
    },

    getFormatItems( key ) {
      return this.formats[ key ].map( l => l.key );
    },
    getFormatNames( key ) {
      return this.formats[ key ].map( l => l.name );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'ServerSettings' );
    },

    close() {
      this.$emit( 'close' );
    }
  }
}
</script>
