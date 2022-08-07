import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = {}

/**
 * @param {string} url
 * @returns {Promise<any>}
 */
export const useFetchMirror = url => useApiFetch(
  useApiParameterUrl('/api/mirrors/{url}', {
    url
  }),
  {
    initialData,
    refetch: true
  }
).get().json()
