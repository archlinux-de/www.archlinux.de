module.exports = source => {
  if (this.cacheable) {
    this.cacheable()
  }
  return `module.exports = ${source.replace(/^\/\*\*[^{]+/g, '')}`
}
