import { useApiFetch, useApiUrl } from '~/composables/useApiFetch'

const initialData = {
  count: 0,
  items: [],
  limit: 0,
  offset: 0,
  total: 0
}

/**
 * @param {query, limit, offset} options
 * @returns {Promise<any>}
 */
export const useFetchMirrors = (options) => useApiFetch(
  useApiUrl('/api/mirrors', options),
  {
    initialData,
    refetch: true
  }
).get().json()
