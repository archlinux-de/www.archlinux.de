<template>
  <main class="container">
    <Head>
      <title>Arch Linux Releases</title>
      <link rel="canonical" :href="canonical">
      <meta name="description" v-if="request.query" :content="request.query + '-Installationsmedien für Arch Linux'">
      <meta name="description" v-else content="Übersicht und Download der Arch Linux Installationsmedien">
      <meta name="robots" content="noindex,follow" v-if="data.count < 1">
    </Head>
    <h1 class="mb-4">Arch Linux Releases</h1>

    <div class="input-group mb-3">
      <input
        class="form-control"
        placeholder="Releases suchen"
        type="search"
        autocomplete="off"
        :value="request.query"
        @input="inputSearchQuery"
        data-test="releases-search">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless" v-show="data.total > 0" data-test="releases">
      <thead>
      <tr>
        <th>Version</th>
        <th>Datum</th>
        <th class="d-none d-xl-table-cell text-nowrap">Kernel-Version</th>
        <th class="d-none d-md-table-cell">Größe</th>
      </tr>
      </thead>
      <tbody>
      <tr :key="key" v-for="(item, key) in data.items">
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

    <div class="alert alert-warning" v-if="isFinished && data.total === 0">Keine Releases gefunden</div>

    <div class="row" v-show="data.total > data.limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ data.offset + 1 }} bis {{ data.offset + data.count }} von {{ data.total }} Releases
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
import { ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Head } from '@vueuse/head'
import { useUrlSearchParams } from '@vueuse/core'
import { useFetchReleases } from '~/composables/useFetchReleases'
import prettyBytes from 'pretty-bytes'

const router = useRouter()
const params = useUrlSearchParams('history', { removeFalsyValues: true })

const request = ref({
  query: params.search ?? '',
  limit: 25,
  offset: 0
})

watch(request.value, () => {
  params.search = request.value.query
})

const { data, error, isFinished } = useFetchReleases(request)

const hasNext = computed(() => data.value.total && data.value.total <= data.value.offset + data.value.limit)
const hasPrevious = computed(() => data.value.offset <= 0)

const createQuery = () => {
  const q = {}
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

const canonical = computed(() => window.location.origin + router.resolve({ name: 'news', query: createQuery() }).href)

const validSearchQuery = /[^a-zA-Z0-9@:.+_\- "]+/g

const filterSearchQuery = (query) => query.substring(0, 255).replaceAll(validSearchQuery, '').trim()

const inputSearchQuery = (event) => {
  request.value.query = filterSearchQuery(event.target.value)
}
</script>
