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
  <BaseForm v-bind:title="$t( 'cmd.ChangeOrder' )" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt path="prompt.ChangeOrder"><strong>{{ name }}</strong></Prompt>
    <Draggable class="draggable-container" v-bind:options="{ handle: '.draggable-handle' }" v-model="attributes">
      <div v-for="attribute in attributes" v-bind:key="attribute.id" class="draggable-item">
        <div class="draggable-handle"><span class="fa fa-bars" aria-hidden="true"></span> {{ attribute.name }}</div>
      </div>
    </Draggable>
  </BaseForm>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Access } from '@/constants'

export default {
  props: {
    typeId: Number,
    name: String,
    initialAttributes: Array
  },

  data() {
    return {
      attributes: this.initialAttributes
    };
  },

  methods: {
    submit() {
      const order = this.attributes.map( a => a.id ).join( ',' );
      const initialOrder = this.initialAttributes.map( a => a.id ).join( ',' );

      if ( order == initialOrder ) {
        this.returnToDetails();
        return;
      }

      const data = { typeId: this.typeId, order };

      this.$form.block();

      this.$ajax.post( '/types/attributes/reorder.php', data ).then( ( { changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$form.error( error );
      } );
    },

    returnToDetails() {
      this.$router.push( 'TypeDetails', { typeId: this.typeId } );
    }
  }
}
</script>
