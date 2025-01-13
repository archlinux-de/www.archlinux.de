import { ref, unref, watch } from 'vue'
import { useApiFetch, useApiUrl } from '~/composables/useApiFetch'

const initialData = []
const validTerm = /[^\w\- ]+/g

const filterTerm = (term) => term.substring(0, 255).replaceAll(validTerm, '').trim()

/**
 * @param {string} term
 * @returns {Promise<any>}
 */
export const useFetchPackageSuggestions = (term) => {
  const filteredTerm = ref(unref(term))
  watch(term, (newTerm, oldTerm) => {
    const filteredNewTerm = filterTerm(newTerm)
    if (filteredNewTerm !== oldTerm) {
      filteredTerm.value = filteredNewTerm
    }
  }, { immediate: true })

  return useApiFetch(
    useApiUrl('/packages/suggest', {
      term: filteredTerm
    }),
    {
      initialData,
      refetch: true,
      async beforeFetch ({ url, options, cancel }) {
        if (!unref(filteredTerm)) {
          cancel()
        }

        return {
          options
        }
      },
      onFetchError: (ctx) => {
        ctx.data = initialData
        return ctx
      }
    }
  ).get().json()
}
