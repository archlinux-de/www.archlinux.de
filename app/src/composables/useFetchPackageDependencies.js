import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = []

/**
 * @param {string} repository
 * @param {string} architecture
 * @param {string} name
 * @param {string} type
 * @returns {Promise<any>}
 */
export const useFetchPackageDependencies = (repository, architecture, name, type) => useApiFetch(
  useApiParameterUrl('/api/packages/{repository}/{architecture}/{name}/dependencies/{type}', {
    repository,
    architecture,
    name,
    type
  }),
  {
    initialData,
    refetch: true
  }
).get().json()
