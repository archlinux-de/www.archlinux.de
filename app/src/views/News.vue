<template>
  <main class="container">
    <Head>
      <title>Neuigkeiten</title>
      <link rel="canonical" :href="canonical">
      <meta name="description" v-if="request.query" :content="request.query + '-Neuigkeiten für Arch Linux'">
      <meta name="description" v-else content="Neuigkeiten und Mitteilungen zu Arch Linux">
      <meta name="robots" content="noindex,follow" v-if="data.count < 1">
    </Head>
    <h1 class="mb-4">Neuigkeiten</h1>

    <div class="input-group mb-3">
      <input
        class="form-control"
        placeholder="Neuigkeiten suchen"
        type="search"
        autocomplete="off"
        :value="request.query"
        @input="inputSearchQuery"
        data-test="news-search">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-fixed" v-show="data.total > 0" data-test="news">
      <thead>
        <tr>
          <th class="d-none d-md-table-cell">Veröffentlichung</th>
          <th class="w-75">Titel</th>
          <th class="d-none d-xl-table-cell">Autor</th>
        </tr>
      </thead>
      <tbody>
        <tr :key="key" v-for="(item, key) in data.items">
          <td class="d-none d-md-table-cell">{{ (new Date(item.lastModified)).toLocaleDateString('de-DE') }}</td>
          <td>
            <router-link :to="{name: 'news-item', params: {id: item.id, slug: item.slug}}" data-test="news-item-link">
              {{ item.title }}
            </router-link>
          </td>
          <td class="d-none d-xl-table-cell"><a :href="item.author.uri">{{ item.author.name }}</a></td>
        </tr>
      </tbody>
    </table>

    <div class="alert alert-warning" v-if="isFinished && data.total === 0">Keine Neuigkeiten gefunden</div>

    <div class="row" v-show="data.total > data.limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ data.offset + 1 }} bis {{ data.offset + data.count }} von {{ data.total }} Neuigkeiten
      </div>
      <div class="col-12 col-sm-6 text-end">
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious" data-test="previous">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext" data-test="next">älter</button>
      </div>
    </div>

  </main>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Head } from '@vueuse/head'
import { useUrlSearchParams } from '@vueuse/core'
import { useFetchNewsItems } from '~/composables/useFetchNewsItems'

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

const { data, error, isFinished } = useFetchNewsItems(request)

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

const validSearchQuery = /[^a-zA-Z0-9@:.+_\- ]+/g

const filterSearchQuery = (query) => query.substring(0, 255).replaceAll(validSearchQuery, '').trim()

const inputSearchQuery = (event) => {
  request.value.query = filterSearchQuery(event.target.value)
}
</script>
