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
  <Autocomplete v-if="isAutocomplete" v-bind:id="id" v-bind:value="value" v-bind:maxlength="maxLength" v-bind:items="items" v-bind:multi-select="multiSelect"
                v-on:input="value => valueChanged( value )"/>
  <DatePicker v-else-if="isDatePicker" v-bind:id="id" v-bind:value="value" v-bind:maxlength="maxLength" v-bind:with-time="withTime"
              v-on:input="value => valueChanged( value )"/>
  <textarea v-else-if="isMultiLine" v-bind:id="id" class="form-control" rows="6" v-bind:value="value" v-on:input="valueChanged( $event.target.value )"></textarea>
  <input v-else v-bind:id="id" type="text" class="form-control" v-bind:value="value" v-bind:maxlength="maxLength" v-on:input="valueChanged( $event.target.value )">
</template>

<script>
import { MaxLength } from '@/constants'

export default {
  props: {
    id: String,
    value: String,
    attribute: Object,
    project: Object,
    users: { type: Array, default() { return []; } }
  },
  computed: {
    isAutocomplete() {
      return this.attribute != null && ( this.attribute.type == 'ENUM' || this.attribute.type == 'USER' );
    },
    isDatePicker() {
      return this.attribute != null && this.attribute.type == 'DATETIME';
    },
    isMultiLine() {
      return this.attribute != null && this.attribute.type == 'TEXT' && this.attribute[ 'multi-line' ] == 1;
    },
    maxLength() {
      if ( this.attribute != null && ( this.attribute.type == 'TEXT' || this.attribute.type == 'ENUM' ) && this.attribute[ 'max-length' ] != null )
        return this.attribute[ 'max-length' ];
      else
        return MaxLength.Value;
    },
    items() {
      if ( this.isAutocomplete ) {
        if ( this.attribute.type == 'ENUM' ) {
          return this.attribute.items;
        } else {
          if ( this.attribute.members == 1 && this.memberNames != null )
            return this.memberNames;
          else
            return this.userNames;
        }
      } else {
        return [];
      }
    },
    multiSelect() {
      return this.isAutocomplete && this.attribute[ 'multi-select' ] == 1;
    },
    withTime() {
      return this.isDatePicker && this.attribute.time == 1;
    },
    userNames() {
      return this.users.map( u => u.name );
    },
    memberNames() {
      if ( this.project != null )
        return this.users.filter( u => this.project.members.includes( u.id ) ).map( u => u.name );
      else
        return null;
    }
  },
  methods: {
    valueChanged( value ) {
      this.$emit( 'input', value );
    }
  }
}
</script>
