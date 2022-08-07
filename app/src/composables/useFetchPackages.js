import { useApiFetch, useApiUrl } from '~/composables/useApiFetch'

const initialData = {
  architectures: [],
  count: 0,
  items: [],
  limit: 0,
  offset: 0,
  repositories: [],
  total: 0
}

/**
 * @param {query, limit, offset, architecture, repository } options
 * @returns {Promise<any>}
 */
export const useFetchPackages = (options) => useApiFetch(
  useApiUrl('/api/packages', options),
  {
    initialData,
    refetch: true
  }
).get().json()
