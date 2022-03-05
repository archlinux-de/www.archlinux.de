<template>
  <main class="container">
    <Head>
      <title>Neuigkeiten - archlinux.de</title>
      <link rel="canonical" :href="createCanonical()">
      <meta name="description" v-if="getQuery().search" :content="getQuery().search + '-Neuigkeiten für Arch Linux'">
      <meta name="description" v-else content="Neuigkeiten und Mitteilungen zu Arch Linux">
      <meta name="robots" content="noindex,follow" v-if="count < 1">
    </Head>
    <h1 class="mb-4">Neuigkeiten</h1>

    <div class="input-group mb-3">
      <input
        class="form-control"
        placeholder="Neuigkeiten suchen"
        type="search"
        autocomplete="off"
        v-model="query">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered table-fixed" v-show="total > 0">
      <thead>
        <tr>
          <th class="d-none d-md-table-cell">Veröffentlichung</th>
          <th class="w-75">Titel</th>
          <th class="d-none d-xl-table-cell">Autor</th>
        </tr>
      </thead>
      <tbody>
        <tr :key="key" v-for="(item, key) in items">
          <td class="d-none d-md-table-cell">{{ (new Date(item.lastModified)).toLocaleDateString('de-DE') }}</td>
          <td>
            <router-link :to="{name: 'news-item', params: {id: item.id, slug: item.slug}}">
              {{ item.title }}
            </router-link>
          </td>
          <td class="d-none d-xl-table-cell"><a :href="item.author.uri">{{ item.author.name }}</a></td>
        </tr>
      </tbody>
    </table>

    <div class="alert alert-warning" v-if="total === 0">Keine Neuigkeiten gefunden</div>

    <div class="row" v-show="total > limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }} Neuigkeiten
      </div>
      <div class="col-12 col-sm-6 text-end">
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext">älter</button>
      </div>
    </div>

  </main>
</template>

<script setup>
import { inject, ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Head } from '@vueuse/head'

const router = useRouter()

const apiService = inject('apiService')

const query = ref(useRoute().query.search ?? '')
const items = ref([])
const total = ref(null)
const count = ref(null)
const offset = ref(0)
const error = ref(null)
const limit = ref(25)

const hasNext = computed(() => total.value && total.value <= offset.value + limit.value)
const hasPrevious = computed(() => offset.value <= 0)

const fetchNews = () => apiService.fetchNewsItems({
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
  name: 'news',
  query: getQuery()
}).href

watch(query, () => {
  router.replace({ query: getQuery() })
  fetchNews()
})

watch(offset, () => { fetchNews() })

onMounted(() => { fetchNews() })
</script>
