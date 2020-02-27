/**************************************************************************
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
**************************************************************************/

import Vue from 'vue'
import VueI18n from 'vue-i18n'

import en_US from '@/i18n/en_US'

import { ErrorCode } from '@/constants'

Vue.use( VueI18n );

const translationModules = {
  cs: () => import( /* webpackChunkName: "i18n-cs" */ '@/i18n/cs' ),
  de: () => import( /* webpackChunkName: "i18n-de" */ '@/i18n/de' ),
  es: () => import( /* webpackChunkName: "i18n-es" */ '@/i18n/es' ),
  fr: () => import( /* webpackChunkName: "i18n-fr" */ '@/i18n/fr' ),
  hu: () => import( /* webpackChunkName: "i18n-hu" */ '@/i18n/hu' ),
  it: () => import( /* webpackChunkName: "i18n-it" */ '@/i18n/it' ),
  ko: () => import( /* webpackChunkName: "i18n-ko" */ '@/i18n/ko' ),
  nb: () => import( /* webpackChunkName: "i18n-nb" */ '@/i18n/nb' ),
  nl: () => import( /* webpackChunkName: "i18n-nl" */ '@/i18n/nl' ),
  pl: () => import( /* webpackChunkName: "i18n-pl" */ '@/i18n/pl' ),
  pt_BR: () => import( /* webpackChunkName: "i18n-pt_BR" */ '@/i18n/pt_BR' ),
  ru: () => import( /* webpackChunkName: "i18n-ru" */ '@/i18n/ru' ),
  sr: () => import( /* webpackChunkName: "i18n-sr" */ '@/i18n/sr' ),
  tr: () => import( /* webpackChunkName: "i18n-tr" */ '@/i18n/tr' ),
  uk: () => import( /* webpackChunkName: "i18n-uk" */ '@/i18n/uk' ),
  zh_CN: () => import( /* webpackChunkName: "i18n-zh_CN" */ '@/i18n/zh_CN' )
};

VueI18n.prototype.setLocale = function( locale ) {
  return loadTranslation( this, locale ).then( () => {
    this.locale = locale;
  } );
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

export function fromSystemLocale( systemLocale ) {
  const [ country, language ] = systemLocale.split( '-' );

  if ( translationModules[ country + '_' + language ] != null )
    return country + '_' + language;
  else if ( translationModules[ country ] != null )
    return country;
  else
    return 'en_US';
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
