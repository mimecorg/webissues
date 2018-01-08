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
  <div v-bind:class="[ 'input-group', className ]">
    <input ref="input" type="text" class="form-control" autocomplete="off"
           v-bind:id="id" v-bind:value="value" v-bind:maxlength="maxlength"
           v-on:input="valueChanged" v-on:keydown="keyDown" v-on:blur="close">
    <span v-bind:class="[ 'input-group-btn', 'dropdown-input-group', { open } ]">
      <button class="btn btn-default" type="button" tabindex="-1" v-on:click="toggle()" v-on:mousedown.prevent><span class="fa fa-chevron-down" aria-hidden="true"></span></button>
      <div v-if="open && matchingItems.length > 0" class="dropdown-menu dropdown-menu-both" v-on:mousedown.prevent>
        <div ref="scroll" class="dropdown-menu-scroll">
          <li v-for="item in matchingItems" v-bind:key="item" v-bind:class="{ active: item == currentItem }"><a v-on:click="select( item )">{{ item }}</a></li>
        </div>
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
      currentValue: this.value,
      matchPrefix: null,
      open: false
    }
  },
  computed: {
    matchingItems() {
      return this.items.filter( item => this.matchPrefix == null || item.substr( 0, this.matchPrefix.length ).toLowerCase() == this.matchPrefix );
    },
    currentItem() {
      if ( this.multiSelect )
        return this.currentValue.split( /,\s*/ ).pop();
      else
        return this.currentValue;
    },
    currentItemIndex() {
      return this.matchingItems.findIndex( item => item == this.currentItem );
    }
  },
  watch: {
    value( value ) {
      this.currentValue = value;
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
      }
    },
    dropdown() {
      if ( this.currentItem != '' )
        this.matchPrefix = this.currentItem.toLowerCase();
      else
        this.matchPrefix = null;
      this.open = true;
    },
    close() {
      this.open = false;
    },
    select( item ) {
      this.setItem( item );
      this.close();
    },
    setItem( item ) {
      if ( this.multiSelect ) {
        let parts = this.currentValue.split( /,\s*/ );
        parts.pop();
        parts.push( item );
        this.setValue( parts.join( ', ' ) );
      } else {
        this.setValue( item );
      }
    },
    setValue( value ) {
      this.currentValue = value;
      this.$refs.input.value = value;
      this.$emit( 'input', value );
    },
    valueChanged( e ) {
      this.setValue( e.target.value );
      this.dropdown();
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
          if ( this.$refs.scroll.scrollTop > index * 26 )
            this.$refs.scroll.scrollTop = index * 26
          else if ( this.$refs.scroll.scrollTop < ( index - 9 ) * 26 )
            this.$refs.scroll.scrollTop = ( index - 9 ) * 26;
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
        this.close();
      }
    }
  }
}
</script>
