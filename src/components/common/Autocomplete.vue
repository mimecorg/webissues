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
  <div v-bind:class="[ 'input-group', className ]">
    <input ref="input" type="text" class="form-control" autocomplete="off"
           v-bind:id="id" v-bind:maxlength="maxlength" v-bind:value="text"
           v-on:input="setText( $event.target.value )" v-on:keydown="keyDown" v-on:blur="close">
    <span v-bind:class="[ 'input-group-btn', 'dropdown-input-group', { open } ]">
      <button class="btn btn-default" type="button" tabindex="-1" v-on:click="toggle()" v-on:mousedown.prevent><span class="fa fa-chevron-down" aria-hidden="true"></span></button>
      <div ref="menu" v-if="open && matchingItems.length > 0" class="dropdown-menu dropdown-menu-both" v-on:mousedown.prevent>
        <div ref="scroll" class="dropdown-menu-scroll" v-on:scroll.passive="toggleShadow">
          <li v-for="item in matchingItems" v-bind:key="item" v-bind:class="{ active: item == currentItem }"><a v-on:click="select( item )">{{ item }}</a></li>
        </div>
        <span v-bind:class="[ 'dropdown-shadow-top', { active: shadowTop } ]"></span>
        <span v-bind:class="[ 'dropdown-shadow-bottom', { active: shadowBottom } ]"></span>
      </div>
    </span>
  </div>
</template>

<script>
import { KeyCode } from '@/constants'

export default {
  props: {
    id: String,
    value: String,
    maxlength: Number,
    className: String,
    items: Array,
    multiSelect: Boolean
  },

  data() {
    return {
      text: this.value,
      matchPrefix: null,
      open: false,
      shadowTop: false,
      shadowBottom: false
    }
  },

  computed: {
    matchingItems() {
      return this.items.filter( item => this.matchPrefix == null || item.substr( 0, this.matchPrefix.length ).toLowerCase() == this.matchPrefix );
    },
    currentItem() {
      if ( this.multiSelect )
        return this.text.split( /,\s*/ ).pop();
      else
        return this.text;
    },
    currentItemIndex() {
      return this.matchingItems.findIndex( item => item == this.currentItem );
    }
  },

  watch: {
    value( value ) {
      this.text = value;
    },
    text( value ) {
      this.$emit( 'input', value );
    }
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    toggle( mode ) {
      if ( this.open ) {
        this.close();
      } else {
        this.matchPrefix = null;
        this.open = true;
        this.$refs.input.focus();
        this.$nextTick( () => {
          this.toggleShadow();
          this.scrollMenuToView();
          if ( this.currentItemIndex >= 0 )
            this.scrollItemToView( this.currentItemIndex );
        } );
      }
    },

    setText( text ) {
      this.text = text;
      this.dropdown();
    },

    dropdown() {
      if ( this.currentItem != '' )
        this.matchPrefix = this.currentItem.toLowerCase();
      else
        this.matchPrefix = null;
      this.open = true;
      this.$nextTick( () => {
        this.toggleShadow();
        this.scrollMenuToView()
      } );
    },

    close() {
      this.open = false;
      this.shadowTop = false;
      this.shadowBottom = false;
    },

    select( item ) {
      this.setItem( item );
      this.close();
    },

    setItem( item ) {
      if ( this.multiSelect ) {
        let parts = this.text.split( /,\s*/ );
        parts.pop();
        parts.push( item );
        this.text = parts.join( ', ' );
      } else {
        this.text = item;
      }
    },

    keyDown( e ) {
      if ( e.keyCode == KeyCode.Up || e.keyCode == KeyCode.Down ) {
        if ( !this.open ) {
          this.dropdown();
        } else if ( this.open && this.matchingItems.length > 0 ) {
          let index = this.currentItemIndex;
          if ( e.keyCode == KeyCode.Up ) {
            index--;
            if ( index < 0 )
              index = this.matchingItems.length - 1;
          } else {
            index++;
            if ( index >= this.matchingItems.length )
              index = 0;
          }
          this.setItem( this.matchingItems[ index ] );
          this.scrollItemToView( index );
        }
        e.preventDefault();
      } else if ( e.keyCode == KeyCode.Tab || e.keyCode == KeyCode.Enter ) {
        if ( this.open && this.currentItemIndex >= 0 ) {
          this.close();
          e.preventDefault();
        }
      } else if ( e.keyCode == KeyCode.F4 ) {
        if ( !this.open )
          this.dropdown();
      } else if ( e.keyCode == KeyCode.Esc ) {
        if ( this.open ) {
          this.close();
          e.stopPropagation();
        }
      }
    },

    toggleShadow() {
      if ( this.open && this.matchingItems.length > 0 ) {
        const hasScrollbar = this.$refs.scroll.clientHeight < this.$refs.scroll.scrollHeight;
        const scrollbarWidth = this.$refs.scroll.offsetWidth - this.$refs.scroll.clientWidth;
        const scrolledFromTop = this.$refs.scroll.offsetHeight + this.$refs.scroll.scrollTop;
        const scrolledToTop = this.$refs.scroll.scrollTop <= 0;
        const scrolledToBottom = scrolledFromTop >= this.$refs.scroll.scrollHeight;
        this.shadowTop = hasScrollbar && scrollbarWidth == 0 && !scrolledToTop;
        this.shadowBottom = hasScrollbar && scrollbarWidth == 0 && !scrolledToBottom;
      } else {
        this.shadowTop = false;
        this.shadowBottom = false;
      }
    },

    scrollMenuToView() {
      if ( this.$form != null && this.$refs.menu != null )
        this.$form.scrollMenuToView( this.$el, this.$refs.menu );
    },

    scrollItemToView( index ) {
      const scroll = this.$refs.scroll;
      const child = scroll.children[ index ];
      if ( scroll.scrollTop > child.offsetTop - 5 )
        scroll.scrollTop = child.offsetTop - 5;
      else if ( scroll.scrollTop + scroll.clientHeight < child.offsetTop - 5 + child.clientHeight )
        scroll.scrollTop = child.offsetTop - 5 + child.clientHeight - scroll.clientHeight;
    }
  }
}
</script>
