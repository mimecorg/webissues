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
  <div ref="overlay" id="window-overlay" tabindex="-1" v-bind:class="{ 'window-busy': busy }" v-on:click.self="close">
    <div id="window" v-bind:class="'window-' + size">
      <component v-if="childComponent != null" v-bind:is="childComponent()" v-bind="childProps"
                 v-on:close="close" v-on:block="block" v-on:unblock="unblock" v-on:scrollToAnchor="scrollToAnchor" v-on:error="error"/>
      <BusyOverlay v-if="busy"/>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  data() {
    return {
      top: 0
    };
  },
  computed: {
    ...mapState( 'window', [ 'childComponent', 'childProps', 'size', 'busy' ] )
  },
  watch: {
    busy( value ) {
      if ( value ) {
        this.top = this.$refs.overlay.scrollTop;
        this.$refs.overlay.addEventListener( 'scroll', this.restoreScroll );
      } else {
        this.$refs.overlay.removeEventListener( 'scroll', this.restoreScroll );
      }
    }
  },
  methods: {
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
      if ( element != null ) {
        while ( element != null && element != this.$refs.overlay ) {
          top += element.offsetTop;
          element = element.offsetParent;
        }
        this.$refs.overlay.scrollTop = top;
      }
    },
    error( error ) {
      this.$store.dispatch( 'showError', error );
    },
    handleFocusIn( e ) {
      if ( e.target != document && e.target != this.$refs.overlay && !isChildElement( e.target, this.$refs.overlay ) )
        this.$refs.overlay.focus();
    },
    restoreScroll() {
      this.$refs.overlay.removeEventListener( 'scroll', this.restoreScroll );
      this.$refs.overlay.scrollTop = this.top;
    }
  },
  mounted() {
    this.$refs.overlay.focus();
    document.addEventListener( 'focusin', this.handleFocusIn );
  },
  beforeDestroy() {
    document.removeEventListener( 'focusin', this.handleFocusIn );
  }
}

function isChildElement( element, parent ) {
  while ( element != null ) {
    if ( element.parentElement == parent )
      return true;
    element = element.parentElement;
  }
  return false;
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

#window-overlay {
  position: absolute;
  left: 0; right: 0;
  top: 0; bottom: 0;
  background-color: rgba( 0, 0, 0, 0.5 );
  .touch-scroll();
  z-index: 10;
  outline: 0;

  &.window-busy {
    cursor: wait;
  }
}

#window {
  position: relative;
  width: auto;
  min-height: 66px;
  margin: 60px 10px 10px 10px;
  background-color: @window-bg;
  border: 1px solid @window-border-color;
  border-radius: @border-radius-large;
  .box-shadow( 0 3px 9px rgba( 0, 0, 0, 0.5 ) );

  @media ( min-width: @screen-sm-min ) {
    width: @screen-sm-min - 60px;
    margin: 60px auto 30px auto;
    .box-shadow( 0 5px 15px rgba( 0, 0, 0, 0.5 ) );

    &.window-small {
      width: 400px;
    }
  }

  @media ( min-width: @screen-md-min ) {
    &.window-large {
      width: @screen-md-min - 60px;
    }
  }

  @media ( min-width: @screen-lg-min ) {
    &.window-large {
      width: @screen-lg-min - 60px;
    }
  }
}
</style>
