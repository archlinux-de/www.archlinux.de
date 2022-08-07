import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = []

/**
 * @param {string} repository
 * @param {string} architecture
 * @param {string} name
 * @param {string} type
 * @returns {Promise<any>}
 */
export const useFetchPackageInverseDependencies = (repository, architecture, name, type) => useApiFetch(
  useApiParameterUrl('/api/packages/{repository}/{architecture}/{name}/inverse-dependencies/{type}', {
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
