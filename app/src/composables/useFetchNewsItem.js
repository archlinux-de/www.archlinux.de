import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = {}

/**
 * @param {int} id
 * @returns {Promise<any>}
 */
export const useFetchNewsItem = (id) => useApiFetch(
  useApiParameterUrl('/api/news/{id}', {
    id
  }),
  {
    initialData,
    refetch: true,
    onFetchError: (ctx) => {
      ctx.data = initialData
      return ctx
    }
  }
).get().json()
