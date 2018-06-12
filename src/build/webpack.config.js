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
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const AssetsPlugin = require( 'assets-webpack-plugin' );

module.exports = function( { electron, production } = {} ) {
  if ( production )
    process.env.NODE_ENV = 'production';

  function makeStyleLoader( type ) {
    const cssLoader = {
      loader: 'css-loader',
      options: {
        minimize: production ? { discardComments: { removeAll: true } } : false
      }
    }
    const loaders = [ cssLoader ];
    if ( type )
      loaders.push( type + '-loader' );
    if ( production ) {
      return ExtractTextPlugin.extract( {
        use: loaders,
        fallback: 'vue-style-loader'
      } );
    } else {
      return [ 'vue-style-loader' ].concat( loaders );
    }
  }

  const config = {
    entry: electron ? {
      client: './src/client.js'
    } : {
      common: './src/common.js',
      application: './src/application.js'
    },
    output: {
      path: path.resolve( __dirname, electron ? '../../app/assets' : '../../assets' ),
      publicPath: production ? '../' : 'http://localhost:8080/',
      filename: production ? 'js/[name].min.js?[chunkhash]' : 'js/[name].js',
      library: electron ? undefined : 'WebIssues_[name]'
    },
    module: {
      rules: [
        {
          test: /\.vue$/,
          loader: 'vue-loader',
          options: {
            loaders: {
              css: makeStyleLoader(),
              less: makeStyleLoader( 'less' )
            }
          }
        },
        {
          test: /\.js$/,
          loader: 'babel-loader',
          exclude: /node_modules/
        },
        {
          test: /\.(png|jpg)$/,
          loader: 'file-loader',
          options: {
            name: 'images/[name].[ext]?[hash]'
          }
        },
        {
          test: /\.(eot|svg|ttf|woff|woff2)$/,
          loader: 'file-loader',
          options: {
            name: 'fonts/[name].[ext]?[hash]'
          }
        },
        {
          test: /\.css$/,
          use: makeStyleLoader()
        },
        {
          test: /\.less$/,
          use: makeStyleLoader( 'less' )
        }
      ]
    },
    resolve: {
      extensions: [ '.js', '.vue', '.json' ],
      alias: {
        '@': path.resolve( __dirname, '..' )
      }
    },
    plugins: [
      new webpack.EnvironmentPlugin( {
        NODE_ENV: production ? 'production' : 'development',
        TARGET: electron ? 'electron' : 'web'
      } )
    ],
    devServer: {
      headers: {
        'Access-Control-Allow-Origin': '*'
      },
      contentBase: false,
      noInfo: true,
      hot: true,
      overlay: true
    },
    performance: {
      hints: false
    },
    stats: {
      children: false,
      modules: false
    },
    devtool: production ? false : '#cheap-module-eval-source-map',
    target: electron ? 'electron-renderer' : 'web'
  };

  if ( production ) {
    config.plugins.push( new webpack.optimize.UglifyJsPlugin( {
      compress: {
        warnings: false
      },
      comments: false
    } ) );
    config.plugins.push( new ExtractTextPlugin( {
      filename: 'css/[name].min.css?[contenthash]'
    } ) );
    config.plugins.push( new webpack.BannerPlugin( {
      banner: "Copyright (C) 2007-2017 WebIssues Team | License: AGPLv3"
    } ) );
    if ( !electron ) {
      config.plugins.push( new AssetsPlugin( {
        filename: 'assets.json',
        path: config.output.path,
        fullPath: false
      } ) );
    }
  } else {
    config.plugins.push( new webpack.NamedModulesPlugin() );
    config.plugins.push( new webpack.HotModuleReplacementPlugin() );
  }

  return config;
};
