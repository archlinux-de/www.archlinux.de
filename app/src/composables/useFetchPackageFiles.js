import { useApiFetch, useApiParameterUrl } from '~/composables/useApiFetch'

const initialData = []

/**
 * @param {string} repository
 * @param {string} architecture
 * @param {string} name
 * @returns {Promise<any>}
 */
export const useFetchPackageFiles = (repository, architecture, name) => useApiFetch(
  useApiParameterUrl('/api/packages/{repository}/{architecture}/{name}/files', {
    repository,
    architecture,
    name
  }),
  {
    initialData,
    refetch: true
  }
).get().json()
