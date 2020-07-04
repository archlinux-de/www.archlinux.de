const createApiService = fetch => {
  /**
   * @param {string} url
   * @returns {Promise<any>}
   */
  const fetchJson = url => {
    const controller = new AbortController()
    setTimeout(() => { controller.abort() }, 5000)

    return fetch(url, {
      credentials: 'omit',
      headers: { Accept: 'application/json' },
      signal: controller.signal
    }).then(response => {
      if (response.ok) {
        return response.json()
      }
      throw new Error(response.statusText)
    }).catch(error => {
      throw new Error(`Fetching URL "${url}" failed with "${error.message}"`)
    })
  }

  /**
   * @param {string} path
   * @param {Object} options
   * @returns {string}
   */
  const createUrl = (path, options = {}) => {
    const url = new URL(path, location.toString())
    Object.entries(options)
      .filter((entry) => typeof entry[1] !== 'undefined' && entry[1] !== null && entry[1].toString().length > 0)
      .forEach(entry => { url.searchParams.set(entry[0], entry[1]) })
    url.searchParams.sort()
    return url.toString()
  }

  /**
   * @param {string} path
   * @param {Object} options
   * @returns {string}
   */
  const createParameterUrl = (path, options = {}) => {
    Object.entries(options)
      .filter((entry) => typeof entry[1] !== 'undefined' && entry[1] !== null && entry[1].toString().length > 0)
      .forEach(entry => { path = path.replace('{' + entry[0] + '}', encodeURIComponent(entry[1])) })
    return (new URL(path, location.toString())).toString()
  }

  return {
    /**
     * @param {query, limit, offset, architecture, repository } options
     * @returns {Promise<any>}
     */
    fetchPackages (options) {
      return fetchJson(createUrl('/api/packages', options))
    },

    /**
     * @param {string} repository
     * @param {string} architecture
     * @param {string} name
     * @returns {Promise<any>}
     */
    fetchPackage (repository, architecture, name) {
      return fetchJson(createParameterUrl('/api/packages/{repository}/{architecture}/{name}', {
        repository: repository,
        architecture: architecture,
        name: name
      }))
    },

    /**
     * @param {string} repository
     * @param {string} architecture
     * @param {string} name
     * @returns {Promise<any>}
     */
    fetchPackageFiles (repository, architecture, name) {
      return fetchJson(createParameterUrl('/api/packages/{repository}/{architecture}/{name}/files', {
        repository: repository,
        architecture: architecture,
        name: name
      }))
    },

    /**
     * @param {string} repository
     * @param {string} architecture
     * @param {string} name
     * @param {string} type
     * @returns {Promise<any>}
     */
    fetchPackageInverseDependencies (repository, architecture, name, type) {
      return fetchJson(createParameterUrl('/api/packages/{repository}/{architecture}/{name}/inverse-dependencies/{type}', {
        repository: repository,
        architecture: architecture,
        name: name,
        type: type
      }))
    },

    /**
     * @param {string} repository
     * @param {string} architecture
     * @param {string} name
     * @param {string} type
     * @returns {Promise<any>}
     */
    fetchPackageDependencies (repository, architecture, name, type) {
      return fetchJson(createParameterUrl('/api/packages/{repository}/{architecture}/{name}/dependencies/{type}', {
        repository: repository,
        architecture: architecture,
        name: name,
        type: type
      }))
    },

    /**
     * @param {string} term
     * @returns {Promise<any>}
     */
    fetchPackageSuggestions (term) {
      return fetchJson(createUrl('/packages/suggest', { term: term }))
    },

    /**
     * @param {query, limit, offset} options
     * @returns {Promise<any>}
     */
    fetchNewsItems (options) {
      return fetchJson(createUrl('/api/news', options))
    },

    /**
     * @param {int} id
     * @returns {Promise<any>}
     */
    fetchNewsItem (id) {
      return fetchJson(createParameterUrl('/api/news/{id}', { id: id }))
    },

    /**
     * @param {query, limit, offset} options
     * @returns {Promise<any>}
     */
    fetchMirrors (options) {
      return fetchJson(createUrl('/api/mirrors', options))
    },

    /**
     * @param {string} url
     * @returns {Promise<any>}
     */
    fetchMirror (url) {
      return fetchJson(createParameterUrl('/api/mirrors/{url}', { url: url }))
    },

    /**
     * @param {query, limit, offset} options
     * @returns {Promise<any>}
     */
    fetchReleases (options) {
      return fetchJson(createUrl('/api/releases', options))
    },

    /**
     * @param {string} version
     * @returns {Promise<any>}
     */
    fetchRelease (version) {
      return fetchJson(createParameterUrl('/api/releases/{version}', { version: version }))
    }
  }
}

export default createApiService
