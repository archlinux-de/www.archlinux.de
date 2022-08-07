import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = {}

/**
 * @param {string} version
 * @returns {Promise<any>}
 */
export const useFetchRelease = (version) => useApiFetch(
  useApiParameterUrl('/api/releases/{version}', {
    version
  }),
  {
    initialData,
    refetch: true
  }
).get().json()
