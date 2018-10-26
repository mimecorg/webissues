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
  <div class="btn-group" v-bind:class="{ open }" role="group">
    <button ref="button" type="button"
            v-bind:class="[ 'btn', btnClass, 'dropdown-toggle', { 'dropdown-has-caret': text != null } ]" v-bind:title="title"
            aria-haspopup="true" v-bind:aria-expanded="open ? 'true' : 'false'"
            v-on:click="toggle" v-on:keydown="keyDown">
      <span v-if="faClass != null" v-bind:class="[ 'fa', faClass ]" aria-hidden="true"></span>
      <span v-if="textClass != null && text != null" v-bind:class="textClass">{{ text }}</span><template v-else-if="text != null">{{ text }}</template>
      <span v-if="text != null" class="caret"></span>
    </button>
    <div v-if="open" class="dropdown-backdrop" v-on:click="close"></div>
    <ul ref="menu" v-bind:class="[ 'dropdown-menu', menuClass ]" v-on:click="click" v-on:keydown="keyDown">
      <slot/>
    </ul>
  </div>
</template>

<script>
import { KeyCode } from '@/constants'

export default {
  props: {
    btnClass: { type: String, default: 'btn-default' },
    faClass: String,
    text: String,
    textClass: String,
    menuClass: String,
    title: String
  },

  data() {
    return {
      open: false
    }
  },

  methods: {
    focus() {
      this.$refs.button.focus();
    },

    toggle() {
      if ( this.open ) {
        this.close();
      } else {
        this.open = true;
        this.$refs.button.focus();
        this.$emit( 'open' );
      }
    },
    close() {
      this.open = false;
    },

    click( e ) {
      for ( let el = e.target; el != this.$refs.menu; el = el.parentNode ) {
        if ( el.tagName == 'A' ) {
          this.close();
          break;
        }
      }
    },

    keyDown( e ) {
      if ( e.keyCode == KeyCode.Up || e.keyCode == KeyCode.Down ) {
        if ( !this.open ) {
          this.open = true;
          this.$emit( 'open' );
        } else {
          const items = this.$refs.menu.querySelectorAll( 'li a' );
          if ( items.length > 0 ) {
            let index = indexOfElement( items, e.target );
            if ( e.keyCode == KeyCode.Up && index > 0 )
              index--;
            else if ( e.keyCode == KeyCode.Down && index < items.length - 1 )
              index++;
            if ( index < 0 )
              index = 0;
            items[ index ].focus();
          }
        }
        e.preventDefault();
      } else if ( e.keyCode == KeyCode.Esc ) {
        if ( this.open ) {
          this.$refs.button.focus();
          this.close();
          e.stopPropagation();
        }
      } else if ( e.keyCode == KeyCode.Enter ) {
        this.close();
      }
    }
  }
}

function indexOfElement( elements, el ) {
  return Array.prototype.indexOf.call( elements, el );
}
</script>
