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

const path = require( 'path' );
const webpack = require( 'webpack' );

module.exports = function( { production } = {} ) {
  if ( production )
    process.env.NODE_ENV = 'production';

  const config = {
    mode: production ? 'production' : 'development',
    entry: {
      main: './app/src/main.js'
    },
    output: {
      path: path.resolve( __dirname, '../../assets' ),
      filename: production ? 'js/[name].min.js?[chunkhash]' : 'js/[name].js'
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          loader: 'babel-loader',
          exclude: /node_modules/
        }
      ]
    },
    resolve: {
      extensions: [ '.js', '.json' ],
      alias: {
        '@': path.resolve( __dirname, '..' )
      }
    },
    plugins: [],
    performance: {
      hints: false
    },
    stats: {
      children: false,
      modules: false
    },
    node: {
      __dirname: false,
      __filename: false
    },
    devtool: production ? false : '#cheap-module-eval-source-map',
    target: 'electron-main'
  };

  if ( production ) {
    config.plugins.push( new webpack.BannerPlugin( {
      banner: "Copyright (C) 2007-2017 WebIssues Team | License: AGPLv3"
    } ) );
  }

  return config;
};
