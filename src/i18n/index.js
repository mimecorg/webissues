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
import VueI18n from 'vue-i18n'

import en_US from '@/i18n/en_US'

import { ErrorCode } from '@/constants'

Vue.use( VueI18n );

const translationModules = {
  pl: () => import( /* webpackChunkName: "i18n-pl" */ '@/i18n/pl' )
};

export default function makeI18n( locale ) {
  const i18n = new VueI18n( {
    locale,
    fallbackLocale: 'en_US',
    messages: {
      en_US: convertErrorCodes( en_US )
    }
  } );

  if ( process.env.NODE_ENV != 'production' && module.hot != null ) {
    module.hot.accept( '@/i18n/en_US', () => {
      i18n.setLocaleMessage( 'en_US', convertErrorCodes( en_US ) );
    } );
  }

  return loadTranslation( i18n, locale ).then( () => i18n );
}

function loadTranslation( i18n, locale ) {
  if ( i18n.messages[ locale ] != null || translationModules[ locale ] == null )
    return Promise.resolve();

  if ( process.env.NODE_ENV != 'production' && module.hot != null ) {
    module.hot.accept( './src/i18n/' + locale + '.json', () => {
      updateTranslation( i18n, locale );
    } );
  }

  return updateTranslation( i18n, locale );
}

function updateTranslation( i18n, locale ) {
  return translationModules[ locale ]().then( translation => {
    i18n.setLocaleMessage( locale, convertErrorCodes( translation.default ) );
  } );
}

function convertErrorCodes( dict ) {
  if ( dict.ErrorCode != null ) {
    const converted = {};
    for ( const code in dict.ErrorCode ) {
      if ( ErrorCode[ code ] != null )
        converted[ ErrorCode[ code ] ] = dict.ErrorCode[ code ];
    }
    dict = { ...dict, ErrorCode: converted };
  }
  return dict;
}
