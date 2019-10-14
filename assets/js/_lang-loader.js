module.exports = function (source) {
  if (this.cacheable) this.cacheable()
  source = source.replace(/^\/\*\*[^{]+/g, '')

  let value = JSON.parse(source)
  value = JSON.stringify(value)
  return `module.exports = ${value}`
}
