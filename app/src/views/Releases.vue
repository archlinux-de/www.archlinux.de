<template>
  <main class="container">
    <Head>
      <title>Arch Linux Releases - archlinux.de</title>
      <link rel="canonical" :href="createCanonical()">
      <meta name="description" v-if="getQuery().search" :content="getQuery().search + '-Installationsmedien für Arch Linux'">
      <meta name="description" v-else content="Übersicht und Download der Arch Linux Installationsmedien">
      <meta name="robots" content="noindex,follow" v-if="count < 1">
    </Head>
    <h1 class="mb-4">Arch Linux Releases</h1>

    <div class="input-group mb-3">
      <input
        class="form-control"
        placeholder="Releases suchen"
        type="search"
        autocomplete="off"
        v-model="query"
        data-test="releases-search">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered" v-show="total > 0" data-test="releases">
      <thead>
      <tr>
        <th>Version</th>
        <th>Datum</th>
        <th class="d-none d-xl-table-cell text-nowrap">Kernel-Version</th>
        <th class="d-none d-md-table-cell">Größe</th>
      </tr>
      </thead>
      <tbody>
      <tr :key="key" v-for="(item, key) in items">
        <td><router-link :to="{name: 'release', params: {version: item.version}}" data-test="release-link">{{ item.version }}</router-link></td>
        <td>{{ (new Date(item.releaseDate)).toLocaleDateString('de-DE') }}</td>
        <td>{{ item.kernelVersion }}</td>
        <td>
          <span v-if="item.fileSize">{{ prettyBytes(item.fileSize, { maximumFractionDigits: 0 }) }}</span>
          <span v-else>-</span>
        </td>
      </tr>
      </tbody>
    </table>

    <div class="alert alert-warning" v-if="total === 0">Keine Releases gefunden</div>

    <div class="row" v-show="total > limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }} Releases
      </div>
      <div class="col-12 col-sm-6 text-end">
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious" data-test="previous">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext" data-test="next">älter</button>
      </div>
    </div>

    <div class="mt-4">
      <a class="btn btn-outline-secondary btn-sm" href="/releases/feed">Feed</a>
      <router-link class="btn btn-secondary btn-sm" :to="{name: 'download'}">Aktueller Download</router-link>
    </div>

  </main>
</template>

<script setup>
import { inject, ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Head } from '@vueuse/head'
import prettyBytes from 'pretty-bytes'

const route = useRoute()
const router = useRouter()
const apiService = inject('apiService')

const query = ref(route.query.search ?? '')
const items = ref([])
const total = ref(null)
const count = ref(null)
const offset = ref(0)
const error = ref(null)
const limit = ref(25)

watch(query, () => {
  router.replace({ query: getQuery() })
  fetchReleases()
})
watch(offset, () => {
  fetchReleases()
})

const hasNext = computed(() => total.value && total.value <= offset.value + limit.value)
const hasPrevious = computed(() => offset.value <= 0)

const fetchReleases = () => apiService.fetchReleases({
  query: query.value,
  limit: limit.value,
  offset: offset.value
})
  .then(data => {
    items.value = data.items
    total.value = data.total
    count.value = data.count
    offset.value = data.offset
    error.value = null
  })
  .catch(err => {
    items.value = []
    total.value = null
    count.value = null
    offset.value = 0
    error.value = err
  })

const getQuery = () => {
  const q = {}
  if (query.value) {
    q.search = query.value
  }
  return q
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
  name: 'releases',
  query: getQuery()
}).href

onMounted(() => { fetchReleases() })
</script>
