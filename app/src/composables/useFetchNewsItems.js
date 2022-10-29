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
export const useFetchNewsItems = (options) => useApiFetch(
  useApiUrl('/api/news', options),
  {
    initialData,
    refetch: true,
    onFetchError: (ctx) => {
      ctx.data = initialData
      return ctx
    }
  }
).get().json()
