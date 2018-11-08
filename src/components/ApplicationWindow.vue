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
  <div ref="overlay" id="window-overlay" tabindex="-1" v-bind:class="{ 'window-busy': busy, 'window-auto-close': autoClose }" v-on:click.self="overlayClick">
    <div id="window" v-bind:class="'window-' + size">
      <component v-if="childForm != null" v-bind:is="getForm( childForm )" v-bind="childProps"/>
      <BusyOverlay v-if="busy"/>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { getForm } from '@/components/forms'

export default {
  data() {
    return {
      top: 0,
      savedPositions: [],
      size: 'small',
      autoClose: false
    };
  },

  computed: {
    ...mapState( 'window', [ 'childForm', 'childProps', 'busy' ] )
  },

  watch: {
    childForm( value ) {
      if ( value != null ) {
        this.size = 'normal';
      } else {
        this.size = 'small';
        this.autoClose = false;
      }
    },
    busy( value ) {
      if ( value ) {
        this.top = this.$refs.overlay.scrollTop;
        this.$refs.overlay.addEventListener( 'scroll', this.restoreScroll );
      } else {
        this.$refs.overlay.removeEventListener( 'scroll', this.restoreScroll );
        this.$nextTick( () => {
          if ( !this.$refs.overlay.contains( document.activeElement ) )
            this.$refs.overlay.focus();
        } );
      }
    }
  },

  methods: {
    getForm,

    setSize( size ) {
      this.size = size;
    },
    setAutoClose( autoClose ) {
      this.autoClose = autoClose;
    },

    overlayClick() {
      if ( this.autoClose )
        this.close();
    },

    close() {
      this.$store.dispatch( 'window/close' );
    },

    block() {
      this.$store.commit( 'window/setBusy', true );
    },
    unblock() {
      this.$store.commit( 'window/setBusy', false );
    },

    scrollToAnchor( anchor ) {
      let element = document.getElementById( anchor );
      let top = 0;
      while ( element != null && element != this.$refs.overlay ) {
        top += element.offsetTop;
        element = element.offsetParent;
      }
      if ( element != null )
        element.scrollTop = top;
    },
    scrollMenuToView( element, menu ) {
      const height = menu.offsetTop + menu.clientHeight + 3;
      let top = 0;
      while ( element != null && element != this.$refs.overlay ) {
        top += element.offsetTop;
        element = element.offsetParent;
      }
      if ( element != null && top + height > element.scrollTop + element.clientHeight )
        element.scrollTop = Math.min( top, top + height - element.clientHeight );
    },

    loadPosition( key ) {
      const position = this.savedPositions.find( s => s.key == key );
      if ( position != null )
        this.$refs.overlay.scrollTop = position.top;
      else
        this.$refs.overlay.scrollTop = 0;
    },
    savePosition( key ) {
      this.resetPosition( key );
      this.savedPositions.push( { key, top: this.$refs.overlay.scrollTop } );
    },
    resetPosition( key ) {
      this.savedPositions = this.savedPositions.filter( s => s.key != key && !s.key.startsWith( key + '/' ) );
    },

    error( error ) {
      this.$store.dispatch( 'showError', error );
    },

    handleFocusIn( e ) {
      if ( e.target != document && !this.$refs.overlay.contains( e.target ) )
        this.$refs.overlay.focus();
    },

    restoreScroll() {
      this.$refs.overlay.removeEventListener( 'scroll', this.restoreScroll );
      this.$refs.overlay.scrollTop = this.top;
    }
  },

  form() {
    return {
      setSize: this.setSize,
      setAutoClose: this.setAutoClose,
      close: this.close,
      block: this.block,
      unblock: this.unblock,
      scrollToAnchor: this.scrollToAnchor,
      scrollMenuToView: this.scrollMenuToView,
      loadPosition: this.loadPosition,
      savePosition: this.savePosition,
      resetPosition: this.resetPosition,
      error: this.error
    };
  },

  mounted() {
    this.$refs.overlay.focus();
    document.addEventListener( 'focusin', this.handleFocusIn );
  },

  beforeDestroy() {
    document.removeEventListener( 'focusin', this.handleFocusIn );
  }
}
</script>
