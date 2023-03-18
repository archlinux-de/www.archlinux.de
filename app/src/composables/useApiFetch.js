import { computed, unref } from 'vue'
import { createFetch } from '@vueuse/core'

export const useApiFetch = createFetch({
  options: {
    timeout: 10000
  },
  fetchOptions: {
    headers: { Accept: 'application/json' },
    credentials: 'omit'
  }
})

/**
 * @param {string} path
 * @param {Object} options
 * @returns {Promise<any>}
 */
export const useApiUrl = (path, options = {}) => computed(() => {
  const url = new URL(unref(path), window.location.toString())

  // @TODO: use Map for options
  Object.entries(unref(options))
    .map(entry => [entry[0], unref(entry[1])])
    .filter((entry) => typeof entry[1] !== 'undefined' && entry[1] !== null && entry[1].toString().length > 0)
    .forEach(entry => { url.searchParams.set(entry[0], entry[1]) })

  url.searchParams.sort()

  return url.toString()
})

/**
 * @param {string} pathTemplate
 * @param {Object} options
 * @returns {Promise<any>}
 */
export const useApiParameterUrl = (pathTemplate, options = {}) => computed(() => {
  let path = unref(pathTemplate)

  Object.entries(unref(options))
    .map(entry => [entry[0], unref(entry[1])])
    .filter((entry) => typeof entry[1] !== 'undefined' && entry[1] !== null && entry[1].toString().length > 0)
    .forEach(entry => { path = path.replace('{' + entry[0] + '}', encodeURIComponent(entry[1])) })

  return (new URL(path, window.location.toString())).toString()
})
