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

Vue.mixin( {
  data() {
    const result = {};

    if ( this.$options.fields != null ) {
      this.$fieldsData = {};

      const fields = this.$options.fields.apply( this );

      for ( const name in fields ) {
        const field = fields[ name ];
        const { condition = true, value } = field;
        if ( condition ) {
          result[ name ] = value;
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
      if ( !field.condition.apply( this ) )
        continue;
    }

    if ( field.type == String ) {
      const { required = false, multiLine = false, maxLength, parse } = field;
      try {
        this[ name ] = this.$parser.normalizeString( this[ name ], maxLength, { allowEmpty: !required, multiLine } );
        if ( parse != null )
          this[ name ] = parse( this[ name ] );
      } catch ( error ) {
        if ( error.reason == Reason.APIError )
          this[ name + 'Error' ] = this.$t( 'ErrorCode.' + error.errorCode );
        else if ( error.reason == Reason.ParseError )
          this[ name + 'Error' ] = error.message;
        else
          throw error;
      }
    } else if ( field.type == Number ) {
      const { required = false, requiredError } = field;
      if ( required && this[ name ] == null )
        this[ name + 'Error' ] = requiredError || this.$t( 'ErrorCode.' + ErrorCode.EmptyValue );
    } else {
      throw new Error( 'Invalid field type: ' + name );
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
