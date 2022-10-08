<template>
  <main class="container">
    <Head>
      <title>Mirror-Status</title>
      <link rel="canonical" :href="canonical">
      <meta name="description" v-if="request.query" :content="request.query + '-Mirror für Arch Linux'">
      <meta name="description" v-else content="Paket-Mirror Arch Linux">
      <meta name="robots" content="noindex,follow" v-if="data.count < 1">
    </Head>
    <h1 class="mb-4">Mirror-Status</h1>

    <div class="input-group mb-3">
      <input
        class="form-control"
        placeholder="Mirror suchen"
        type="search"
        autocomplete="off"
        :value="request.query"
        @input="inputSearchQuery"
        data-test="mirrors-search">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered" v-show="data.total > 0" data-test="mirrors">
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
        <tr :key="key" v-for="(item, key) in data.items">
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

    <div class="alert alert-warning" v-if="isFinished && data.total === 0">Keine Mirrors gefunden</div>

    <div class="row" v-show="data.total > data.limit">
      <div class="col-12 col-sm-6 mb-3 text-end text-sm-start">
        {{ data.offset + 1 }} bis {{ data.offset + data.count }} von {{ data.total }} Mirrors
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
import { useFetchMirrors } from '~/composables/useFetchMirrors'

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

const { data, error, isFinished } = useFetchMirrors(request)

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
</script>
