module.exports = {
  root: true,
  env: {
    node: true,
    browser: true
  },
  extends: [
    'eslint:recommended',
    'standard',
    'plugin:compat/recommended'
  ],
  parserOptions: {
    parser: 'babel-eslint'
  }
}
