module.exports = {
  presets: [
    [ '@babel/preset-env', {
      targets: {
        browsers: [ 'defaults' ]
      },
      modules: false,
      useBuiltIns: 'entry',
      corejs: 3
    } ]
  ],
  plugins: [ '@babel/plugin-proposal-object-rest-spread', '@babel/plugin-syntax-dynamic-import' ],
  comments: true,
  env: {
    test: {
      presets: [
        [ '@babel/preset-env', {
          targets: {
            node: 10
          },
          modules: 'commonjs',
          useBuiltIns: false
        } ]
      ],
      plugins: [
        [ 'babel-plugin-module-resolver', {
          alias: {
            '@': './src'
          }
        } ]
      ]
    }
  }
};
