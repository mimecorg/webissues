<!--
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
  <DropdownButton ref="dropdown" v-bind="$attrs" v-on:open="open">
    <template v-slot:button>
      <slot name="button"/>
    </template>
    <slot name="filter"/>
    <div v-if="hasScroll" ref="scroll" v-bind:class="[ 'dropdown-menu-scroll', menuScrollClass ]" v-on:scroll.passive="toggleShadow">
      <slot/>
    </div>
    <slot v-else name="no-scroll"/>
    <span v-if="hasScroll" v-bind:class="[ 'dropdown-shadow-top', { active: top } ]"></span>
    <span v-if="hasScroll" v-bind:class="[ 'dropdown-shadow-bottom', { active: bottom } ]"></span>
  </DropdownButton>
</template>

<script>
export default {
  props: {
    hasScroll: { type: Boolean, default: true },
    autoScroll: { type: Boolean, default: false },
    menuScrollClass: String
  },

  data() {
    return {
      top: false,
      bottom: false
    };
  },

  watch: {
    hasScroll() {
      this.$nextTick( () => {
        this.toggleShadow();
      } );
    }
  },

  methods: {
    focus() {
      this.$refs.dropdown.focus();
    },
    expand() {
      this.$refs.dropdown.expand();
    },

    toggleShadow() {
      if ( this.hasScroll ) {
        const hasScrollbar = this.$refs.scroll.clientHeight < this.$refs.scroll.scrollHeight;
        const scrollbarWidth = this.$refs.scroll.offsetWidth - this.$refs.scroll.clientWidth;
        const scrolledFromTop = this.$refs.scroll.offsetHeight + this.$refs.scroll.scrollTop;
        const scrolledToTop = this.$refs.scroll.scrollTop <= 0;
        const scrolledToBottom = scrolledFromTop >= this.$refs.scroll.scrollHeight;
        this.top = hasScrollbar && scrollbarWidth == 0 && !scrolledToTop;
        this.bottom = hasScrollbar && scrollbarWidth == 0 && !scrolledToBottom;
      } else {
        this.top = false;
        this.bottom = false;
      }
    },

    open() {
      this.$nextTick( () => {
        this.toggleShadow();
        if ( this.autoScroll )
          this.scrollItemToView();
      } );
      this.$emit( 'open' );
    },

    scrollItemToView() {
      for ( let i = 0; i < this.$refs.scroll.children.length; i++ ) {
        const item = this.$refs.scroll.children[ i ];
        if ( item.className == 'active' ) {
          if ( this.$refs.scroll.scrollTop > item.offsetTop - 5 )
            this.$refs.scroll.scrollTop = item.offsetTop - 5;
          else if ( this.$refs.scroll.scrollTop + this.$refs.scroll.clientHeight < item.offsetTop - 5 + item.clientHeight )
            this.$refs.scroll.scrollTop = item.offsetTop - 5 + item.clientHeight - this.$refs.scroll.clientHeight;
          break;
        }
      }
    }
  }
}
</script>
