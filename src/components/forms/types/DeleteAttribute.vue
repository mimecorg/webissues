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
  <BaseForm v-bind:title="$t( 'cmd.DeleteAttribute' )" size="small" with-buttons v-on:ok="submit" v-on:cancel="returnToDetails">
    <Prompt path="prompt.DeleteAttribute"><strong>{{ name }}</strong></Prompt>
    <Prompt v-if="force" path="prompt.WarningDeleteAttribute" alert-class="alert-danger"><strong>{{ $t( 'label.Warning' ) }}</strong></Prompt>
  </BaseForm>
</template>

<script>
import { ErrorCode, Reason } from '@/constants'

export default {
  props: {
    typeId: Number,
    attributeId: Number,
    name: String,
    used: Boolean
  },

  data() {
    return {
      force: this.used
    };
  },

  methods: {
    submit() {
      this.$form.block();

      const data = { attributeId: this.attributeId, force: this.force };

      this.$ajax.post( '/types/attributes/delete.php', data ).then( () => {
        this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.CannotDeleteAttribute ) {
          this.$form.unblock();
          this.force = true;
        } else {
          this.$form.error( error );
        }
      } );
    },

    returnToDetails() {
      this.$router.push( 'TypeDetails', { typeId: this.typeId } );
    }
  }
}
</script>
