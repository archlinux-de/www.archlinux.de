<template>
  <main class="container">
    <Head>
      <title>Mirror-Status - archlinux.de</title>
      <link rel="canonical" :href="createCanonical()">
      <meta name="description" v-if="getQuery().search" :content="getQuery().search + '-Mirror für Arch Linux'">
      <meta name="description" v-else content="Paket-Mirror Arch Linux">
      <meta name="robots" content="noindex,follow" v-if="count < 1">
    </Head>
    <h1 class="mb-4">Mirror-Status</h1>

    <div class="input-group mb-3">
      <input
        class="form-control"
        placeholder="Mirror suchen"
        type="search"
        autocomplete="off"
        v-model="query"
        data-test="mirrors-search">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered" v-show="total > 0" data-test="mirrors">
      <thead>
        <tr>
          <th>URL</th>
          <th class="d-none d-md-table-cell">Land</th>
          <th class="d-none d-lg-table-cell text-nowrap">∅ Antwortzeit</th>
          <th class="d-none d-lg-table-cell text-nowrap">∅ Verzögerung</th>
          <th class="d-none d-sm-table-cell">Datum</th>
          <th class="d-none d-xl-table-cell text-center">IPv4</th>
          <th class="d-none d-md-table-cell text-center">IPv6</th>
        </tr>
      </thead>
      <tbody>
        <tr :key="key" v-for="(item, key) in items">
          <td><a :href="item.url" rel="nofollow noopener" target="_blank" data-test="mirror-link">{{ item.host }}</a></td>
          <td class="d-none d-md-table-cell">{{ item.country ? item.country.name : '' }}</td>
          <td class="d-none d-lg-table-cell">{{ renderDuration(item.durationAvg) }}</td>
          <td class="d-none d-lg-table-cell">{{ renderDuration(item.delay) }}</td>
          <td class="d-none d-sm-table-cell">{{ (new Date(item.lastSync)).toLocaleDateString('de-DE') }}</td>
          <td class="d-none d-xl-table-cell text-center">
            <span v-if="item.ipv4" class="text-success">✓</span>
            <span v-else class="text-danger">×</span>
          </td>
          <td class="d-none d-md-table-cell text-center">
            <span v-if="item.ipv6" class="text-success">✓</span>
            <span v-else class="text-danger">×</span>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="alert alert-warning" v-if="total === 0">Keine Mirrors gefunden</div>

    <div class="row" v-show="total > limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }} Mirrors
      </div>
      <div class="col-12 col-sm-6 text-end">
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious" data-test="previous">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext" data-test="next">älter</button>
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

const fetchMirrors = () => apiService.fetchMirrors({
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

const renderDuration = (data) => {
  if (data) {
    if (data < 0) {
      data = 0
    }

    let unit = 's'
    const secondsPerMinute = 60
    const secondsPerHour = secondsPerMinute * 60
    const secondsPerDay = secondsPerHour * 24
    if (data >= secondsPerDay) {
      unit = 'd'
      data = data / secondsPerDay
    } else if (data >= secondsPerHour) {
      unit = 'h'
      data = data / secondsPerHour
    } else if (data >= secondsPerMinute) {
      unit = 'min'
      data = data / secondsPerMinute
    }

    return new Intl.NumberFormat('de-DE').format(data) + ' ' + unit
  }
  return data
}

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
  name: 'mirrors',
  query: getQuery()
}).href

watch(query, () => {
  router.replace({ query: getQuery() })
  fetchMirrors()
})
watch(offset, () => { fetchMirrors() })
onMounted(() => { fetchMirrors() })
</script>
