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
        v-model="query">
    </div>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <table class="table table-striped table-responsive table-sm table-borderless table-bordered" v-show="total > 0">
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
        <td><router-link :to="{name: 'release', params: {version: item.version}}">{{ item.version }}</router-link></td>
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
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext">älter</button>
      </div>
    </div>

    <div class="mt-4">
      <a class="btn btn-outline-secondary btn-sm" href="/releases/feed">Feed</a>
      <router-link class="btn btn-secondary btn-sm" :to="{name: 'download'}">Aktueller Download</router-link>
    </div>

  </main>
</template>

<script>
import prettyBytes from 'pretty-bytes'
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
      this.fetchReleases()
    },
    offset () {
      this.fetchReleases()
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
    fetchReleases () {
      return this.apiService.fetchReleases({
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
    prettyBytes,
    createCanonical () {
      return window.location.origin + this.$router.resolve({
        name: 'releases',
        query: this.getQuery()
      }).href
    }
  },
  mounted () {
    this.fetchReleases()
  }
}
</script>
