<!--
import { throws } from 'assert';
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
  <div class="container-fluid">
    <FormHeader v-if="!closeHidden" v-bind:title="title" v-bind:breadcrumbs="breadcrumbs" v-on:close="close">
      <slot name="header"/>
    </FormHeader>
    <div v-else class="form-header">
      <h1>{{ title }}</h1>
    </div>
    <slot/>
    <FormButtons v-if="withButtons" v-bind:ok-hidden="okHidden" v-bind:cancel-hidden="cancelHidden" v-on:ok="ok" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { KeyCode } from '@/constants'

export default {
  props: {
    title: String,
    breadcrumbs: Array,
    size: { type: String, default: 'normal' },
    closeHidden: Boolean,
    autoClose: Boolean,
    savePosition: Boolean,
    withButtons: Boolean,
    okHidden: Boolean,
    cancelHidden: Boolean
  },
  watch: {
    title: {
      handler( value ) {
        if ( process.env.TARGET == 'web' )
          document.title = value + ' | ' + this.$store.state.global.serverName;
      },
      immediate: true
    },
    size: {
      handler( value ) {
        this.$form.setSize( value );
      },
      immediate: true
    },
    autoClose: {
      handler( value ) {
        this.$form.setAutoClose( value );
      },
      immediate: true
    }
  },
  methods: {
    close() {
      this.$form.close();
    },
    ok() {
      this.$emit( 'ok' );
    },
    cancel() {
      this.$emit( 'cancel' );
    },
    handleKeyDown( e ) {
      if ( this.autoClose && e.keyCode == KeyCode.Esc )
        this.close();
    }
  },
  mounted() {
    if ( this.savePosition )
      this.$form.loadPosition( this.$router.path );
    document.addEventListener( 'keydown', this.handleKeyDown );
  },
  beforeDestroy() {
    document.removeEventListener( 'keydown', this.handleKeyDown );
  },
  routeChanged( route, fromRoute ) {
    if ( this.savePosition )
      this.$form.savePosition( fromRoute.path );
  }
}
</script>
