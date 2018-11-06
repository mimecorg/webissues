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
  <div class="container-fluid">
    <FormHeader v-bind:title="title" v-on:close="close">
      <button v-if="mode == 'edit'" type="button" class="btn btn-default" v-on:click="deleteAttribute">
        <span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'cmd.Delete' ) }}
      </button>
    </FormHeader>
    <Prompt v-if="mode == 'edit'" path="prompt.ModifyAttribute"><strong>{{ initialName }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="prompt.AddAttribute"><strong>{{ typeName }}</strong></Prompt>
    <FormInput ref="name" id="name" v-bind:label="$t( 'label.Name' )" v-bind="$field( 'name' )" v-model="name"/>
    <FormDropdown ref="attributeType" v-bind:label="$t( 'label.AttributeType' )" v-bind="$field( 'attributeType' )"
                  v-bind:items="attributeTypes" v-bind:item-names="attibuteTypeNames" v-model="attributeType"/>
    <Panel v-bind:title="$t( 'title.AttributeDetails' )">
      <template v-if="attributeType == 'TEXT'">
        <FormCheckbox v-bind:label="$t( 'text.AllowMultipleLines' )" v-model="multiLine"/>
        <FormInput id="minLength" v-bind:label="$t( 'label.MinimumLength' )" v-bind="$field( 'minLength' )" v-model="minLength"/>
        <FormInput id="maxLength" v-bind:label="$t( 'label.MaximumLength' )" v-bind="$field( 'maxLength' )" v-model="maxLength"/>
      </template>
      <template v-else-if="attributeType == 'ENUM'">
        <FormCheckbox v-bind:label="$t( 'text.AllowCustomValues' )" v-model="editable"/>
        <FormCheckbox v-bind:label="$t( 'text.AllowMultipleItems' )" v-model="multiSelect"/>
        <FormGroup id="items" v-bind:label="$t( 'label.DropdownListItems' )" v-bind:help="$t( 'prompt.EnterDropdownListItems' )" v-bind="$field( 'items' )">
          <textarea id="items" class="form-control" rows="6" v-model="items" v-on:change="extractItems"></textarea>
        </FormGroup>
        <FormInput id="minLength" v-bind:label="$t( 'label.MinimumLength' )" v-bind:disabled="!editable || multiSelect" v-bind="$field( 'minLength' )" v-model="minLength"/>
        <FormInput id="maxLength" v-bind:label="$t( 'label.MaximumLength' )" v-bind:disabled="!editable || multiSelect" v-bind="$field( 'maxLength' )" v-model="maxLength"/>
      </template>
      <template v-else-if="attributeType == 'NUMERIC'">
        <FormDropdown id="decimal" v-bind:label="$t( 'label.DecimalPlaces' )" v-bind="$field( 'decimal' )"
                      v-bind:items="decimalItems" v-bind:item-names="decimalNames" v-model="decimal"/>
        <FormInput id="minValue" v-bind:label="$t( 'label.MinimumValue' )" v-bind="$field( 'minValue' )" v-model="minValue"/>
        <FormInput id="maxValue" v-bind:label="$t( 'label.MaximumValue' )" v-bind="$field( 'maxValue' )" v-model="maxValue"/>
        <FormCheckbox v-bind:label="$t( 'text.StripTrailingZeros' )" v-bind:disabled="decimal == 0" v-model="strip"/>
      </template>
      <template v-else-if="attributeType == 'DATETIME'">
        <FormGroup v-bind:label="$t( 'label.DateSettings' )">
          <div class="radio">
            <label><input type="radio" v-bind:checked="!time && !local" v-on:change="time = false, local = false"> {{ $t( 'text.DateOnly' ) }}</label>
          </div>
          <div class="radio">
            <label><input type="radio" v-bind:checked="time && !local" v-on:change="time = true, local = false"> {{ $t( 'text.DateAndTime' ) }}</label>
          </div>
          <div class="radio">
            <label><input type="radio" v-bind:checked="time && local" v-on:change="time = true, local = true"> {{ $t( 'text.DateAndTimeLocal' ) }}</label>
          </div>
        </FormGroup>
      </template>
      <template v-else-if="attributeType == 'USER'">
        <FormCheckbox v-bind:label="$t( 'text.AllowOnlyMembers' )" v-model="members"/>
        <FormCheckbox v-bind:label="$t( 'text.AllowMultipleItems' )" v-model="multiSelect"/>
      </template>
    </Panel>
    <FormCheckbox v-bind:label="$t( 'text.AttributeIsRequired' )" v-model="required"/>
    <FormGroup id="defaultValue" v-bind:label="$t( 'label.DefaultValue' )" v-bind="$field( 'defaultValue' )">
      <ValueEditor id="defaultValue" v-bind:attribute="attribute" with-expressions v-model="defaultValue"/>
    </FormGroup>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { MaxLength, ErrorCode, Reason } from '@/constants'
import { makeError } from '@/utils/errors'

export default {
  props: {
    mode: String,
    typeId: Number,
    initialName: String,
    attributeId: Number,
    typeName: String,
    initialType: String,
    initialDetails: Object
  },

  fields() {
    const details = this.mode == 'edit' ? this.initialDetails : { decimal: 0 };

    const fields = {
      name: {
        value: this.initialName,
        type: String,
        required: true,
        maxLength: MaxLength.Name
      },
      attributeType: {
        value: this.initialType || 'TEXT',
        type: String,
        required: true
      },
      multiLine: {
        value: details[ 'multi-line' ],
        type: Boolean
      },
      minLength: {
        value: details[ 'min-length' ],
        type: String,
        maxLength: 3,
        condition: () => this.hasMinMaxLength,
        parse: this.parseMinLength
      },
      maxLength: {
        value: details[ 'max-length' ],
        type: String,
        maxLength: 3,
        condition: () => this.hasMinMaxLength,
        parse: this.parseMaxLength
      },
      editable: {
        value: details.editable,
        type: Boolean,
      },
      multiSelect: {
        value: details[ 'multi-select' ],
        type: Boolean
      },
      items: {
        type: String,
        multiLine: true,
        required: true,
        condition: () => this.attributeType == 'ENUM',
        parse: this.parseItems
      },
      decimal: {
        value: details.decimal,
        type: Number,
        required: true,
        condition: () => this.attributeType == 'NUMERIC',
      },
      minValue: {
        type: String,
        condition: () => this.attributeType == 'NUMERIC',
        parse: this.parseMinValue
      },
      maxValue: {
        type: String,
        condition: () => this.attributeType == 'NUMERIC',
        parse: this.parseMaxValue
      },
      strip: {
        value: details.strip,
        type: Boolean,
      },
      time: {
        value: details.time,
        type: Boolean,
      },
      local: {
        value: details.local,
        type: Boolean,
      },
      members: {
        value: details.members,
        type: Boolean,
      },
      required: {
        value: details.required,
        type: Boolean,
      },
      defaultValue: {
        type: String,
        maxLength: MaxLength.Value,
        parse: this.parseDefaultValue
      }
    };

    if ( details.items != null )
      fields.items.value = details.items.join( "\n" );
    if ( details[ 'min-value' ] != null )
      fields.minValue.value = this.$formatter.convertAttributeValue( details[ 'min-value' ], { type: 'NUMERIC', decimal: details.decimal, strip: details.strip } );
    if ( details[ 'max-value' ] != null )
      fields.maxValue.value = this.$formatter.convertAttributeValue( details[ 'max-value' ], { type: 'NUMERIC', decimal: details.decimal, strip: details.strip } );
    if ( details.default != null )
      fields.defaultValue.value = this.$formatter.formatExpression( details.default, { type: this.initialType, ...details } );

    return fields;
  },

  data() {
    const data = {
      extractedItems: [],
      parsedItems: [],
    };

    if ( this.initialDetails != null && this.initialDetails.items != null )
        data.extractedItems = this.initialDetails.items;

    return data;
  },

  computed: {
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'cmd.ModifyAttribute' );
      else if ( this.mode == 'add' )
        return this.$t( 'cmd.AddAttribute' );
    },
    attributeTypes() {
      if ( this.initialType == 'TEXT' || this.initialType == 'ENUM' || this.initialType == 'USER' )
        return [ 'TEXT', 'ENUM', 'USER' ];
      else if ( this.initialType != null )
        return [ this.initialType ];
      else
        return [ 'TEXT', 'ENUM', 'NUMERIC', 'DATETIME', 'USER' ];
    },
    attibuteTypeNames() {
      return this.attributeTypes.map( t => this.$t( 'AttributeType.' + t ) );
    },
    hasMinMaxLength() {
      return this.attributeType == 'TEXT' || this.attributeType == 'ENUM' && this.editable && !this.multiSelect;
    },
    decimalItems() {
      return Array( 7 ).fill( 0 ).map( ( x, i ) => i );
    },
    decimalNames() {
      return this.decimalItems.map( n => n.toString() );
    },
    attribute() {
      return {
        type: this.attributeType,
        items: this.extractedItems,
        'multi-select': this.multiSelect ? 1 : 0,
        time: this.time ? 1 : 0
      };
    }
  },

  watch: {
    attributeType() {
      this.$fields.clear();
    }
  },

  methods: {
    submit() {
      if ( !this.$fields.validate() )
        return;

      if ( this.mode == 'edit' && !this.$fields.modified() ) {
        this.returnToDetails( this.typeId );
        return;
      }

      const data = {};
      if ( this.mode == 'add' )
        data.typeId = this.typeId;
      else
        data.attributeId = this.attributeId;
      data.name = this.name;
      data.type = this.attributeType;

      const details = this.extractDetails();

      if ( this.required )
        details.required = 1;

      if ( this.defaultValue != '' ) {
        const attribute = { type: this.attributeType, ...details };
        details.default = this.$parser.convertExpression( this.defaultValue, attribute );;
      }

      data.details = details;

      this.$emit( 'block' );

      this.$ajax.post( '/types/attributes/' + this.mode + '.php', data ).then( ( { attributeId, changed } ) => {
        if ( changed )
          this.$store.commit( 'global/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        if ( error.reason == Reason.APIError && error.errorCode == ErrorCode.AttributeAlreadyExists ) {
          this.$emit( 'unblock' );
          this.nameError = this.$t( 'ErrorCode.' + error.errorCode );
          this.$nextTick( () => {
            this.$refs.name.focus();
          } );
        } else {
          this.$emit( 'error', error );
        }
      } );
    },

    parseMinLength( value ) {
      if ( value != '' )
        return this.$parser.parseInteger( value, 1, MaxLength.Value ).toString();
      else
        return '';
    },
    parseMaxLength( value ) {
      if ( value != '' ) {
        const min = ( this.minLength != '' && this.minLengthError == null ) ? Number( this.minLength ) : 1;
        return this.$parser.parseInteger( value, min, MaxLength.Value ).toString();
      } else {
        return '';
      }
    },

    parseItems( value ) {
      const lines = value.split( "\n" );
      const items = [];
      const min = ( this.hasMinMaxLength && this.minLength != '' && this.minLengthError == null ) ? Number( this.minLength ) : 1;
      const max = ( this.hasMinMaxLength && this.maxLength != '' && this.maxLengthError == null ) ? Number( this.maxLength ) : MaxLength.Value;
      for ( let i in lines ) {
        const item = this.$parser.normalizeString( lines[ i ], max, { allowEmpty: true } );
        if ( item != '' ) {
          if ( item.length < min )
            throw makeError( ErrorCode.StringTooShort );
          if ( items.indexOf( item ) >= 0 )
            throw makeError( ErrorCode.DuplicateItems );
          items.push( item );
        }
      }
      this.parsedItems = items;
      return items.join( "\n" );
    },

    parseMinValue( value ) {
      if ( value != '' )
        return this.$parser.normalizeAttributeValue( value, { type: 'NUMERIC', decimal: this.decimal, strip: this.strip ? 1 : 0 } );
      else
        return '';
    },
    parseMaxValue( value ) {
      if ( value != '' ) {
        const min = this.minValueError == null ? this.$parser.convertAttributeValue( this.minValue, { type: 'NUMERIC', decimal: this.decimal } ) : null;
        return this.$parser.normalizeAttributeValue( value, { type: 'NUMERIC', decimal: this.decimal, 'min-value': min, strip: this.strip ? 1 : 0 } );
      } else {
        return '';
      }
    },

    parseDefaultValue( value ) {
      if ( value == '' )
        return value;

      const details = this.extractDetails();

      if ( this.attributeType == 'TEXT' )
        details[ 'multi-line' ] = 0;
      else if ( this.attributeType == 'ENUM' && details.items == null )
        details.editable = 1;

      const attribute = { type: this.attributeType, ...details };

      return this.$parser.normalizeExpression( value, attribute );
    },

    extractDetails() {
      const details = {};

      switch ( this.attributeType ) {
        case 'TEXT':
          if ( this.multiLine )
            details[ 'multi-line' ] = 1;
          if ( this.minLength != '' && this.minLengthError == null )
            details[ 'min-length' ] = Number( this.minLength );
          if ( this.maxLength != '' && this.maxLengthError == null )
            details[ 'max-length' ] = Number( this.maxLength );
          break;

        case 'ENUM':
          if ( this.editable )
            details.editable = 1;
          if ( this.multiSelect )
            details[ 'multi-select' ] = 1;
          if ( this.itemsError == null )
            details.items = this.parsedItems;
          if ( this.hasMinMaxLength ) {
            if ( this.minLength != '' && this.minLengthError == null )
              details[ 'min-length' ] = Number( this.minLength );
            if ( this.maxLength != '' && this.maxLengthError == null )
              details[ 'max-length' ] = Number( this.maxLength );
          }
          break;

        case 'NUMERIC':
          details.decimal = this.decimal;
          if ( this.minValue != '' && this.minValueError == null )
            details[ 'min-value' ] = this.$parser.convertAttributeValue( this.minValue, { type: 'NUMERIC', decimal: this.decimal } );
          if ( this.maxValue != '' && this.maxValueError == null )
            details[ 'max-value' ] = this.$parser.convertAttributeValue( this.maxValue, { type: 'NUMERIC', decimal: this.decimal } );
          if ( this.decimal != 0 && this.strip )
            details.strip = 1;
          break;

        case 'DATETIME':
          if ( this.time )
            details.time = 1;
          if ( this.local )
            details.local = 1;
          break;

        case 'USER':
          if ( this.members )
            details.members = 1;
          if ( this.multiSelect )
            details[ 'multi-select' ] = 1;
          break;
      }

      return details;
    },

    extractItems() {
      const lines = this.items.split( "\n" );
      const items = [];
      for ( let i in lines ) {
        try {
          const item = this.$parser.normalizeString( lines[ i ], MaxLength.Value, { allowEmpty: true } );
          if ( item != '' && items.indexOf( item ) < 0 )
            items.push( item );
        } catch ( e ) {
        }
      }
      this.extractedItems = items;
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'TypeDetails', { typeId: this.typeId } );
    },

    deleteAttribute() {
      this.$router.push( 'DeleteAttribute', { typeId: this.typeId, attributeId: this.attributeId } );
    },

    close() {
      this.$emit( 'close' );
    }
  },

  mounted() {
    this.$refs.name.focus();
  }
}
</script>
