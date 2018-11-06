/**************************************************************************
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
**************************************************************************/

import Vue from 'vue'

import { ErrorCode, Reason } from '@/constants'
import { makeParseError } from '@/utils/errors'

Vue.mixin( {
  data() {
    const result = {};

    if ( this.$options.fields != null ) {
      this.$fieldsData = {};

      const fields = this.$options.fields.apply( this );

      for ( const name in fields ) {
        const field = fields[ name ];
        const { condition = true, type } = field;
        if ( condition ) {
          if ( type == String ) {
            if ( field.value == null )
              field.value = '';
            else if ( typeof field.value == 'number' )
              field.value = field.value.toString();
          } else if ( type == Boolean ) {
            if ( field.value == null )
              field.value = false;
            else if ( typeof field.value == 'number' )
              field.value = field.value == 1;
          } else if ( type != Number ) {
            throw new Error( 'Invalid field type: ' + name );
          }
          result[ name ] = field.value;
          result[ name + 'Error' ] = null;
          this.$fieldsData[ name ] = field;
        }
      }
    }

    return result;
  },

  created() {
    if ( this.$fieldsData != null ) {
      this.$field = field.bind( this );
      this.$fields = {
        validate: validate.bind( this ),
        modified: modified.bind( this ),
        clear: clear.bind( this )
      };
    }
  }
} );

function field( name ) {
  const { required = false, maxLength } = this.$fieldsData[ name ];

  const result = { required };
  if ( maxLength != null )
    result.maxlength = maxLength;
  result.error = this[ name + 'Error' ];

  return result;
}

function validate() {
  let valid = true;

  for ( const name in this.$fieldsData ) {
    const field = this.$fieldsData[ name ];

    this[ name + 'Error' ] = null;

    if ( typeof field.condition == 'function' ) {
      if ( !field.condition() )
        continue;
    }

    try {
      if ( field.type == String ) {
        const { required = false, multiLine = false, maxLength } = field;
        this[ name ] = this.$parser.normalizeString( this[ name ], maxLength, { allowEmpty: !required, multiLine } );
      } else if ( field.type == Number ) {
        if ( field.required && this[ name ] == null )
          throw makeParseError( field.requiredError || this.$t( 'ErrorCode.' + ErrorCode.EmptyValue ) );
      }
      if ( field.parse != null )
        this[ name ] = field.parse( this[ name ] );
    } catch ( error ) {
      if ( error.reason == Reason.APIError )
        this[ name + 'Error' ] = this.$t( 'ErrorCode.' + error.errorCode );
      else if ( error.reason == Reason.ParseError )
        this[ name + 'Error' ] = error.message;
      else
        throw error;
    }

    if ( this[ name + 'Error' ] != null ) {
      if ( valid ) {
        if ( field.focus != null )
          field.focus();
        else if ( this.$refs[ name ] != null && this.$refs[ name ].focus != null )
          this.$refs[ name ].focus();
      }
      valid = false;
    }
  }

  return valid;
}

function modified() {
  for ( const name in this.$fieldsData ) {
    if ( this[ name ] != this.$fieldsData[ name ].value )
      return true;
  }
  return false;
}

function clear() {
  for ( const name in this.$fieldsData )
    this[ name + 'Error' ] = null;
}
