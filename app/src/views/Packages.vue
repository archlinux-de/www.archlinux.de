<template>
  <main class="container">
    <Head>
      <title>Paket-Suche</title>
      <link rel="canonical" :href="canonical">
      <meta name="description" v-if="request.query" :content="request.query + '-Pakete für Arch Linux'">
      <meta name="description" v-else content="Übersicht und Suche von Arch Linux-Paketen">
      <meta name="robots" content="noindex,follow" v-if="data.count < 1">
    </Head>
    <h1 class="mb-4">Paket-Suche</h1>

    <div class="input-group mb-3">
      <input
        class="form-control w-75"
        placeholder="Pakete suchen"
        type="search"
        autocomplete="off"
        :value="request.query"
        @input="inputSearchQuery"
        data-test="packages-search">

      <select class="form-select" v-model="request.repository" data-test="packages-filter-repository">
        <option key="0" value="" :selected="data.repository === ''"></option>
        <option :key="key" :value="item" :selected="data.repository === item" v-for="(item, key) in data.repositories">{{ item }}</option>
      </select>
      <select class="form-select d-none d-sm-block" v-if="request.architecture" v-model="request.architecture" data-test="packages-filter-architecture">
        <option key="0" value="" :selected="data.architecture === ''"></option>
        <option :key="key" :value="item" :selected="data.architecture === item" v-for="(item, key) in data.architectures">{{ item }}</option>
      </select>
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-fixed" v-show="data.total > 0" data-test="packages">
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
        <tr :key="key" v-for="(item, key) in data.items">
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

    <div class="alert alert-warning" v-if="isFinished && data.total === 0">Keine Pakete gefunden</div>

    <div class="row" v-show="data.total > data.limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ data.offset + 1 }} bis {{ data.offset + data.count }} von {{ data.total }} Paketen
      </div>
      <div class="col-12 col-sm-6 text-end">
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious" data-test="previous">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext" data-test="next">älter</button>
      </div>
    </div>

  </main>
</template>

<style>
  @media (width >= 1200px) {
    .w-lg-25 {
      width: 25% !important;
    }
  }
</style>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRouter, onBeforeRouteUpdate } from 'vue-router'
import { Head } from '@vueuse/head'
import { useUrlSearchParams } from '@vueuse/core'
import { useFetchPackages } from '~/composables/useFetchPackages'
import PackagePopularity from '~/components/PackagePopularity'

const router = useRouter()
const params = useUrlSearchParams('history', { removeFalsyValues: true })

const request = ref({
  query: params.search ?? '',
  limit: 25,
  offset: 0,
  architecture: params.architecture ?? '',
  repository: params.repository ?? ''
})

watch(request.value, () => {
  params.search = request.value.query
  params.architecture = request.value.architecture
  params.repository = request.value.repository
})

onBeforeRouteUpdate((to, from, next) => {
  next()
  // @TODO: reevaluate if condition is needed
  if (from.query.architecture !== to.query.architecture || from.query.repository !== to.query.repository) {
    request.value.architecture = to.query.architecture
    request.value.repository = to.query.repository
  }
  request.value.query = to.query.search
})

const { data, error, isFinished } = useFetchPackages(request)

const hasNext = computed(() => data.value.total && data.value.total <= data.value.offset + data.value.limit)
const hasPrevious = computed(() => data.value.offset <= 0)

const createQuery = () => {
  const q = {}
  if (request.value.architecture) {
    q.architecture = request.value.architecture
  }
  if (request.value.repository) {
    q.repository = request.value.repository
  }
  if (request.value.query) {
    q.search = request.value.query
  }
  return q
}

const next = () => {
  if (hasNext.value) {
    return
  }
  request.value.offset += request.value.limit
}

const previous = () => {
  if (hasPrevious.value) {
    return
  }
  request.value.offset -= request.value.limit
}

const canonical = computed(() => window.location.origin + router.resolve({ name: 'packages', query: createQuery() }).href)

const validSearchQuery = /[^a-zA-Z0-9@:.+_\- ]+/g

const filterSearchQuery = (query) => query.substring(0, 255).replaceAll(validSearchQuery, '').trim()

const inputSearchQuery = (event) => {
  request.value.query = filterSearchQuery(event.target.value)
}
</script>
