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
  <div class="markup-editor">
    <FormGroup v-bind:id="id" v-bind:label="label" v-bind:error="error">
      <div v-if="isMarkup" class="btn-toolbar">
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.Bold' ) + ' (Ctrl+B)'" v-on:click="markupBold"><span class="fa fa-bold"></span></button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.Italic' ) + ' (Ctrl+I)'" v-on:click="markupItalic"><span class="fa fa-italic"></span></button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.Monospace' )" v-on:click="markupMonospace"><span class="fa fa-code"></span></button>
        </div>
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.Hyperlink' ) + ' (Ctrl+K)'" v-on:click="markupLink"><span class="fa fa-link"></span></button>
        </div>
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.BulletList' )" v-on:click="markupList"><span class="fa fa-list-ul"></span></button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.QuoteBlock' )" v-on:click="markupQuote"><span class="fa fa-quote-right"></span></button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.CodeBlock' )" v-on:click="markupCode"><span class="fa fa-file-code-o"></span></button>
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.RightToLeftText' )" v-on:click="markupRTL"><span class="fa fa-long-arrow-left"></span></button>
        </div>
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-default" v-bind:title="$t( 'MarkupEditor.Preview' )" v-on:click="preview"><span class="fa fa-search"></span></button>
        </div>
      </div>
      <textarea ref="textarea" v-bind:id="id" class="form-control" rows="10" v-bind:value="value" v-bind:maxlength="settings.commentMaxLength"
                v-on:input="valueChanged( $event.target.value )" v-on:keydown="keyDown"></textarea>
      <div v-if="isMarkup && previewHtml != null" class="markup-preview">
        <div class="formatted-text" v-html="previewHtml"></div>
      </div>
    </FormGroup>
    <FormGroup v-bind:label="$t( 'MarkupEditor.TextFormat' )" v-bind:required="true">
      <div class="dropdown-select">
        <DropdownButton v-bind:text="formatName">
          <li v-bind:class="{ active: !isMarkup }"><HyperLink v-on:click="selectMarkup( false )">{{ $t( 'MarkupEditor.PlainText' ) }}</HyperLink></li>
          <li v-bind:class="{ active: isMarkup }"><HyperLink v-on:click="selectMarkup( true )">{{ $t( 'MarkupEditor.TextWithMarkup' ) }}</HyperLink></li>
        </DropdownButton>
      </div>
    </FormGroup>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import { TextFormat, KeyCode } from '@/constants'

export default {
  props: {
    id: String,
    value: String,
    format: Number,
    label: String,
    error: String
  },

  data() {
    return {
      previewHtml: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'settings' ] ),
    isMarkup() {
      return this.format == TextFormat.TextWithMarkup;
    },
    formatName() {
      if ( this.isMarkup )
        return this.$t( 'MarkupEditor.TextWithMarkup' );
      else
        return this.$t( 'MarkupEditor.PlainText' );
    }
  },

  methods: {
    focus() {
      this.$refs.textarea.focus();
    },
    valueChanged( value ) {
      this.$emit( 'input', value );
    },
    selectMarkup( markup ) {
      if ( markup ) {
        this.$emit( 'select-format', TextFormat.TextWithMarkup );
      } else {
        this.previewHtml = null;
        this.$emit( 'select-format', TextFormat.PlainText );
      }
    },

    markupBold() {
      this.markupMultiline( '', '', '**', '**' );
    },
    markupItalic() {
      this.markupMultiline( '', '', '__', '__' );
    },
    markupMonospace() {
      this.markupMultiline( '', '', '`', '`' );
    },
    markupLink() {
      const url = window.prompt( this.$t( 'MarkupEditor.EnterLinkURL' ), 'http://' );
      if ( url != null )
        this.markup( '[' + url + ' ', ']', this.$t( 'MarkupEditor.LinkText' ) );
    },
    markupList() {
      this.markupMultiline( '[list]\n', '\n[/list]', '* ', '' );
    },
    markupQuote() {
      this.markup( '[quote]\n', '\n[/quote]', '' );
    },
    markupCode() {
      this.markup( '[code]\n', '\n[/code]', '' );
    },
    markupRTL() {
      this.markup( '[rtl]\n', '\n[/rtl]', '' );
    },

    markup( openBlockWith, closeBlockWith, placeholder ) {
      const scrollTop = this.$refs.textarea.scrollTop;
      this.$refs.textarea.focus();

      const selection = this.getSelection();

      let block;
      let start = selection.caretPosition;
      let length = 0;

      if ( selection.text != '' ) {
        const trailingNewlines = selection.text.match( /[ \t\r\n]*$/ );
        if ( trailingNewlines != null )
          block = openBlockWith + selection.text.replace( /[ \t\r\n]*$/, '' ) + closeBlockWith + trailingNewlines[ 0 ];
        else
          block = openBlockWith + selection.text + closeBlockWith;
        start += block.length;
      } else {
        block = openBlockWith + placeholder + closeBlockWith;
        start += openBlockWith.length;
        length = placeholder.length;
      }

      this.updateSelection( selection, block, start, length );

      this.$refs.textarea.scrollTop = scrollTop;
    },

    markupMultiline( openBlockWith, closeBlockWith, openWith, closeWith ) {
      const scrollTop = this.$refs.textarea.scrollTop;
      this.$refs.textarea.focus();

      const selection = this.getSelection();

      let block;
      let start = selection.caretPosition;
      let length = 0;

      if ( selection.text != '' ) {
        const lines = selection.text.split( /\r?\n/ );
        const blocks = lines.map( line => {
          const trailingSpaces = line.match( /[ \t]+$/ );
          if ( trailingSpaces != null )
            line = line.replace( /[ \t]+$/, '' );
          if ( line != '' || lines.length == 1 )
            return openWith + line + closeWith + ( trailingSpaces != null ? trailingSpaces[ 0 ] : '' );
          else
            return trailingSpaces != null ? trailingSpaces[ 0 ] : '';
        } );
        block = blocks.join( '\n' );
        const trailingNewlines = block.match( /[\r\n]*$/ );
        if ( trailingNewlines != null )
          block = openBlockWith + block.replace( /[\r\n]*$/, '' ) + closeBlockWith + trailingNewlines[ 0 ];
        else
          block = openBlockWith + block + closeBlockWith;
        start += block.length;
      } else {
        block = openBlockWith + openWith + closeWith + closeBlockWith;
        start += openBlockWith.length + openWith.length;
      }

      this.updateSelection( selection, block, start, length );

      this.$refs.textarea.scrollTop = scrollTop;
    },

    getSelection() {
      const textarea = this.$refs.textarea;

      let text;
      let caretPosition;

      if ( document.selection != null ) {
        const range = document.selection.createRange();
        text = range.text;
        if ( window.browser.msie != null ) {
          const rangeCopy = range.duplicate();
          rangeCopy.moveToElementText( textarea );
          caretPosition = -1;
          while ( rangeCopy.inRange( range ) ) {
            rangeCopy.moveStart( 'character' );
            caretPosition++;
          }
        } else {
          caretPosition = textarea.selectionStart;
        }
      } else {
        caretPosition = textarea.selectionStart;
        text = textarea.value.substring( caretPosition, textarea.selectionEnd );
      }

      return { text, caretPosition };
    },

    updateSelection( selection, text, start, length ) {
      const textarea = this.$refs.textarea;

      if ( document.selection != null ) {
        const range = document.selection.createRange();
        range.text = text;
      } else {
        textarea.value = textarea.value.substring( 0, selection.caretPosition ) + text + textarea.value.substring( selection.caretPosition + selection.text.length, textarea.value.length );
      }

      if ( textarea.createTextRange != null ) {
        const range = textarea.createTextRange();
        range.collapse( true );
        range.moveStart( 'character', start );
        range.moveEnd( 'character', length );
        range.select();
      } else if ( textarea.setSelectionRange != null ) {
        textarea.setSelectionRange( start, start + length );
      }

      this.valueChanged( textarea.value );
    },

    preview() {
      this.$refs.textarea.focus();
      this.$ajax.post( '/server/api/issue/preview.php', { text: this.$refs.textarea.value } ).then( html => {
        this.previewHtml = html;
      } ).catch ( error => {
        this.$emit( 'error', error );
      } );
    },

    keyDown( e ) {
      if ( e.keyCode == KeyCode.Tab ) {
        if ( !e.shiftKey && !e.ctrlKey && !e.altKey && !e.metaKey ) {
          const selection = this.getSelection();
          this.updateSelection( selection, '\t', selection.caretPosition + 1, 0 );
        }
        e.preventDefault();
      } else if ( this.isMarkup && ( e.ctrlKey || e.metaKey ) && !e.shiftKey && !e.altKey ) {
        const keys = {
          B: this.markupBold,
          I: this.markupItalic,
          K: this.markupLink
        };
        for ( const key in keys ) {
          if ( keys.hasOwnProperty( key ) && e.keyCode == key.charCodeAt( 0 ) ) {
            keys[ key ].apply( this );
            e.preventDefault();
            break;
          }
        }
      }
    }
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.markup-editor .btn-toolbar {
  margin-bottom: 5px;
}

.markup-preview {
  padding: 10px;
  margin-top: 5px;
  border: 1px solid @issue-border-color;
  border-radius: @border-radius-base;
}
</style>
