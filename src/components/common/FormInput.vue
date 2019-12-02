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
  <FormGroup v-bind:id="id" v-bind:label="label" v-bind:required="required" v-bind:error="error">
    <input ref="input" v-bind:id="id" v-bind:type="type" class="form-control" v-bind:value="value" v-bind="$attrs" v-on="inputListeners">
  </FormGroup>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    id: String,
    type: { type: String, default: 'text' },
    value: String,
    label: String,
    required: Boolean,
    error: String
  },
  computed: {
    inputListeners() {
      return {
        ...this.$listeners,
        input: this.valueChanged
      };
    }
  },
  methods: {
    focus() {
      this.$refs.input.focus();
    },
    valueChanged( e ) {
      this.$emit( 'input', e.target.value );
    }
  }
}
</script>
