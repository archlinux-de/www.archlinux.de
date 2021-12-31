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
        v-model="query">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered" v-show="total > 0">
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
          <td><a :href="item.url" rel="nofollow noopener" target="_blank">{{ item.host }}</a></td>
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
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext">älter</button>
      </div>
    </div>

  </main>
</template>

<script>
import { Head } from '@vueuse/head'

export default {
  components: {
    Head
  },
  inject: ['apiService'],
  data () {
    return {
      query: this.$route.query.search ?? '',
      items: [],
      total: null,
      count: null,
      offset: 0,
      error: null,
      limit: 25
    }
  },
  watch: {
    query () {
      this.$router.replace({ query: this.getQuery() })
      this.fetchMirrors()
    },
    offset () {
      this.fetchMirrors()
    }
  },
  computed: {
    hasNext: function () {
      return this.total && this.total <= this.offset + this.limit
    },
    hasPrevious: function () {
      return this.offset <= 0
    }
  },
  methods: {
    fetchMirrors () {
      return this.apiService.fetchMirrors({
        query: this.query,
        limit: this.limit,
        offset: this.offset
      })
        .then(data => {
          this.items = data.items
          this.total = data.total
          this.count = data.count
          this.offset = data.offset
          this.error = null
        })
        .catch(error => {
          this.items = []
          this.total = null
          this.count = null
          this.offset = 0
          this.error = error
        })
    },
    renderDuration (data) {
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
    },
    getQuery () {
      const query = {}
      if (this.$data.query) {
        query.search = this.$data.query
      }
      return query
    },
    next () {
      if (this.hasNext) {
        return
      }
      this.offset += this.limit
    },
    previous () {
      if (this.hasPrevious) {
        return
      }
      this.offset -= this.limit
    },
    createCanonical () {
      return window.location.origin + this.$router.resolve({
        name: 'mirrors',
        query: this.getQuery()
      }).href
    }
  },
  mounted () {
    this.fetchMirrors()
  }
}
</script>
