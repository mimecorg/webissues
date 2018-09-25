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
  <div v-bind:class="[ 'form-group', { 'has-error': error != null } ]">
    <label v-if="label != null" v-bind:for="id" class="control-label">{{ required ? label + ' *' : label }}</label>
    <slot/>
    <p v-if="error != null || help != null" class="help-block">{{ error != null ? error : help }}</p>
  </div>
</template>

<script>
export default {
  props: {
    id: String,
    label: String,
    required: Boolean,
    error: String,
    help: String
  }
}
</script>
