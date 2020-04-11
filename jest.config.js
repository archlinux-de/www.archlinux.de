module.exports = {
  moduleFileExtensions: ['js', 'json', 'vue', 'scss'],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/assets/$1'
  },
  transform: {
    '\\.js$': [require.resolve('babel-jest'), {
      plugins: ['@babel/plugin-transform-runtime'],
      presets: ['@babel/preset-env']
    }]
  }
}
