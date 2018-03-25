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

Vue.mixin( {
  data() {
    const result = {};

    if ( this.$options.fields != null ) {
      this.$fieldsData = {};

      const fields = this.$options.fields.apply( this );

      for ( const name in fields ) {
        const field = fields[ name ];
        const { condition = true, value, required = false, maxLength } = field;
        if ( condition ) {
          result[ name ] = {
            value,
            required,
            maxLength,
            error: null
          };
          this.$fieldsData[ name ] = field;
        }
      }
    }

    return result;
  },

  created() {
    if ( this.$fieldsData != null ) {
      this.$fields = {
        validate: validate.bind( this ),
        modified: modified.bind( this )
      };
    }
  }
} );

function validate() {
  let valid = true;

  for ( const name in this.$fieldsData ) {
    const field = this.$fieldsData[ name ];

    this[ name ].error = null;

    if ( field.type == String ) {
      const { required = false, multiLine = false, maxLength } = field;
      try {
        this[ name ].value = this.$parser.normalizeString( this[ name ].value, maxLength, { allowEmpty: !required, multiLine } );
      } catch ( error ) {
        if ( error.reason == 'APIError' )
          this[ name ].error = this.$t( 'ErrorCode.' + error.errorCode );
        else
          throw error;
      }
    } else {
      throw new Error( 'Invalid field type: ' + name );
    }

    if ( this[ name ].error != null ) {
      if ( valid && this.$refs[ name ] != null && this.$refs[ name ].focus != null )
        this.$refs[ name ].focus();
      valid = false;
    }
  }

  return valid;
}

function modified() {
  for ( const name in this.$fieldsData ) {
    if ( this[ name ].value != this.$fieldsData[ name ].value )
      return true;
  }
  return false;
}
