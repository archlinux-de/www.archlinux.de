import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = {}

/**
 * @param {string} repository
 * @param {string} architecture
 * @param {string} name
 * @returns {Promise<any>}
 */
export const useFetchPackage = (repository, architecture, name) => useApiFetch(
  useApiParameterUrl('/api/packages/{repository}/{architecture}/{name}', {
    repository,
    architecture,
    name
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
