<template>
  <main class="container">
    <Head>
      <title>Paket-Suche - archlinux.de</title>
      <link rel="canonical" :href="createCanonical()">
      <meta name="description" v-if="getQuery().search" :content="getQuery().search + '-Pakete für Arch Linux'">
      <meta name="description" v-else content="Übersicht und Suche von Arch Linux-Paketen">
      <meta name="robots" content="noindex,follow" v-if="count < 1">
    </Head>
    <h1 class="mb-4">Paket-Suche</h1>

    <div class="input-group mb-3">
      <input
        class="form-control w-75"
        placeholder="Pakete suchen"
        type="search"
        autocomplete="off"
        v-model="query"
        data-test="packages-search">

      <select class="form-select" v-model="repository" data-test="packages-filter-repository">
        <option :key="key" :value="item" :selected="repository === item" v-for="(item, key) in repositories">{{ item }}</option>
      </select>
      <select class="form-select d-none d-sm-block" v-if="architecture" v-model="architecture" data-test="packages-filter-architecture">
        <option :key="key" :value="item" :selected="architecture === item" v-for="(item, key) in architectures">{{ item }}</option>
      </select>
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered table-fixed" v-show="total > 0" data-test="packages">
      <thead>
        <tr>
          <th class="d-none d-lg-table-cell">Repositorium</th>
          <th class="d-none d-xl-table-cell">Architektur</th>
          <th>Name</th>
          <th>Version</th>
          <th class="d-none d-sm-table-cell w-50 w-lg-25">Beschreibung</th>
          <th class="d-none d-lg-table-cell">Datum</th>
          <th class="d-none d-xl-table-cell">Beliebtheit</th>
        </tr>
      </thead>
      <tbody>
        <tr :key="key" v-for="(item, key) in items">
          <td class="d-none d-lg-table-cell">
            <router-link class="d-none d-lg-table-cell"
              :to="{name: 'packages', query: {repository: item.repository.name, architecture: item.repository.architecture}}"
              data-test="package-repository-link">
              {{ item.repository.name }}
            </router-link>
          </td>
          <td class="d-none d-xl-table-cell">{{ item.architecture }}</td>
          <td class="text-break">
            <router-link
              :to="{name: 'package', params: {repository: item.repository.name, architecture: item.repository.architecture, name: item.name}}"
              data-test="package-link">
              {{ item.name }}
            </router-link>
          </td>
          <td class="text-break">{{ item.version }}</td>
          <td class="d-none d-sm-table-cell text-break">{{ item.description }}</td>
          <td class="d-none d-lg-table-cell">{{ (new Date(item.buildDate)).toLocaleDateString('de-DE') }}</td>
          <td class="d-none d-xl-table-cell"><package-popularity :popularity="item.popularity"></package-popularity></td>
        </tr>
      </tbody>
    </table>

    <div class="alert alert-warning" v-if="total === 0">Keine Pakete gefunden</div>

    <div class="row" v-show="total > limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }} Paketen
      </div>
      <div class="col-12 col-sm-6 text-end">
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious" data-test="previous">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext" data-test="next">älter</button>
      </div>
    </div>

  </main>
</template>

<style>
  @media (min-width: 1200px) {
    .w-lg-25 {
      width: 25% !important;
    }
  }
</style>

<script setup>
import { inject, ref, watch, onMounted, computed } from 'vue'
import { useRoute, useRouter, onBeforeRouteUpdate } from 'vue-router'
import { Head } from '@vueuse/head'
import PackagePopularity from '../components/PackagePopularity'

const route = useRoute()
const router = useRouter()
const apiService = inject('apiService')

const query = ref(route.query.search ?? '')
const architecture = ref(route.query.architecture ?? '')
const repository = ref(route.query.repository ?? '')
const items = ref([])
const total = ref(null)
const count = ref(null)
const offset = ref(0)
const architectures = ref([])
const repositories = ref([])
const error = ref(null)
const limit = ref(25)

onBeforeRouteUpdate((to, from, next) => {
  next()
  if (from.query.architecture !== to.query.architecture || from.query.repository !== to.query.repository) {
    architecture.value = to.query.architecture
    repository.value = to.query.repository
    fetchPackages()
  }
})

const hasNext = computed(() => total.value && total.value <= offset.value + limit.value)
const hasPrevious = computed(() => offset.value <= 0)

const fetchPackages = () => apiService.fetchPackages({
  query: query.value,
  limit: limit.value,
  offset: offset.value,
  architecture: architecture.value,
  repository: repository.value
})
  .then(data => {
    items.value = data.items
    total.value = data.total
    count.value = data.count
    offset.value = data.offset
    repositories.value = ['', ...data.repositories]
    architectures.value = ['', ...data.architectures]
    error.value = null
  })
  .catch(err => {
    items.value = []
    total.value = null
    count.value = null
    offset.value = 0
    repositories.value = []
    architectures.value = []
    error.value = err
  })

const getQuery = () => {
  const q = {}
  if (architecture.value) {
    q.architecture = architecture.value
  }
  if (repository.value) {
    q.repository = repository.value
  }
  if (query.value) {
    q.search = query.value
  }
  return q
}

const updateRoute = () => {
  const fromQuery = route.query
  const toQuery = getQuery()
  if (fromQuery.architecture !== toQuery.architecture ||
    fromQuery.repository !== toQuery.repository ||
    fromQuery.search !== toQuery.search) {
    router.replace({ query: toQuery })
  }
}

const next = () => {
  if (hasNext.value) {
    return
  }
  offset.value += limit.value
}

const previous = () => {
  if (hasPrevious.value) {
    return
  }
  offset.value -= limit.value
}

const createCanonical = () => window.location.origin + router.resolve({
  name: 'packages',
  query: getQuery()
}).href

watch(query, () => {
  if (query.value.length > 255) {
    query.value = query.value.substring(0, 255)
  }

  query.value = query.value.replace(/(^[^a-zA-Z0-9]|[^a-zA-Z0-9@:.+_\- ]+)/, '')

  updateRoute()
  fetchPackages()
})
watch(repository, () => {
  updateRoute()
  fetchPackages()
})
watch(architecture, () => {
  updateRoute()
  fetchPackages()
})
watch(offset, () => {
  fetchPackages()
})

onMounted(() => {
  fetchPackages()
})
</script>
