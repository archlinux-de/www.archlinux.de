<template>
  <table class="table table-borderless caption-top" v-if="suggestions.items.length > 0">
    <caption>Gefundene Vorschläge zu <strong>{{ name }}</strong>:</caption>
    <tbody>
      <tr :key="id" v-for="(suggestion, id) in suggestions.items">
        <td>
          <router-link
            :to="{name: 'package', params: {repository: suggestion.repository.name, architecture: suggestion.repository.architecture, name: suggestion.name}}">
            {{ suggestion.name }}
          </router-link>
        </td>
        <td> {{ suggestion.description }}</td>
        <td class="d-none d-lg-table-cell">
          <package-popularity :popularity="suggestion.popularity"></package-popularity>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
import PackagePopularity from '~/components/PackagePopularity'
import { useFetchPackages } from '~/composables/useFetchPackages'

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  limit: {
    type: Number,
    required: true
  }
})

const { data: suggestions } = useFetchPackages({ query: props.name, limit: props.limit })
</script>
