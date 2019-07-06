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
  <BaseForm v-bind:title="$t( 'title.Reports' )" v-bind:size="size" auto-close save-position>
    <template v-if="isAdministrator">
      <FormSection v-bind:title="$t( 'title.PublicReports' )">
        <button type="button" class="btn btn-success" v-on:click="addPublicReport" v-bind:title="$t( 'cmd.AddPublicReport' )">
          <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
        </button>
      </FormSection>
      <Grid v-if="publicReports.length > 0" v-bind:items="publicReports" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-medium', 'column-medium', null, null ]"
            v-on:row-click="rowClickPublic">
        <template slot-scope="{ item, columnIndex, columnClass, columnKey }">
          <td v-bind:key="columnKey" v-bind:class="columnClass" v-html="getCellValue( columnIndex, item )"></td>
        </template>
      </Grid>
      <Prompt v-else path="info.NoPublicReports"/>
    </template>
    <FormSection v-bind:title="$t( 'title.PersonalReports' )">
      <button type="button" class="btn btn-success" v-on:click="addPersonalReport" v-bind:title="$t( 'cmd.AddPersonalReport' )">
        <span class="fa fa-plus" aria-hidden="true"></span> {{ $t( 'cmd.Add' ) }}
      </button>
    </FormSection>
    <Grid v-if="personalReports.length > 0" v-bind:items="personalReports" v-bind:column-names="columnNames" v-bind:column-classes="[ 'column-medium', 'column-medium', null, null ]"
          v-on:row-click="rowClickPersonal">
      <template slot-scope="{ item, columnIndex, columnClass, columnKey }">
        <td v-bind:key="columnKey" v-bind:class="columnClass" v-html="getCellValue( columnIndex, item )"></td>
      </template>
    </Grid>
    <Prompt v-else path="info.NoPersonalReports"/>
  </BaseForm>
</template>

<script>
import { mapGetters } from 'vuex'

import { AlertType, AlertFrequency } from '@/constants'

export default {
  props: {
    publicReports: Array,
    personalReports: Array
  },

  computed: {
    ...mapGetters( 'global', [ 'isAdministrator' ] ),
    size() {
      if ( this.isAdministrator && this.publicReports.length > 0 || this.personalReports.length > 0 )
        return 'large';
      else
        return 'normal';
    },
    columnNames() {
      return [
        this.$t( 'title.Filter' ),
        this.$t( 'title.Location' ),
        this.$t( 'title.Type' ),
        this.$t( 'title.Frequency' )
      ];
    }
  },

  methods: {
    getCellValue( columnIndex, report ) {
      switch ( columnIndex ) {
        case 0:
          return report.view;
        case 1:
          return report.location;
        case 2:
          if ( report.type == AlertType.ChangeReport )
            return this.$t( 'text.ChangeReport' );
          else if ( report.type == AlertType.IssueReport )
            return this.$t( 'text.IssueReport' );
          break;
        case 3:
          if ( report.frequency == AlertFrequency.Daily )
            return this.$t( 'text.Daily' );
          else if ( report.frequency == AlertFrequency.Weekly )
            return this.$t( 'text.Weekly' );
          break;
      }
    },

    addPublicReport() {
      this.$router.push( 'AddPublicReport' );
    },
    addPersonalReport() {
      this.$router.push( 'AddPersonalReport' );
    },

    rowClickPublic( rowIndex ) {
      this.$router.push( 'EditReport', { reportId: this.publicReports[ rowIndex ].id } );
    },
    rowClickPersonal( rowIndex ) {
      this.$router.push( 'EditReport', { reportId: this.personalReports[ rowIndex ].id } );
    }
  }
}
</script>
