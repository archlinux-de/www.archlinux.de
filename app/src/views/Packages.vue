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

      <select class="form-select" v-model="repository">
        <option :key="key" :value="item" :selected="repository === item" v-for="(item, key) in repositories">{{ item }}</option>
      </select>
      <select class="form-select d-none d-sm-block" v-if="architecture" v-model="architecture">
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
                         :to="{name: 'packages', query: {repository: item.repository.name, architecture: item.repository.architecture}}">
              {{ item.repository.name }}
            </router-link>
          </td>
          <td class="d-none d-xl-table-cell">{{ item.architecture }}</td>
          <td class="text-break">
            <router-link
              :to="{name: 'package', params: {repository: item.repository.name, architecture: item.repository.architecture, name: item.name}}">
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
        <button class="btn btn-sm btn-outline-primary" @click="previous" :disabled="hasPrevious">neuer</button>
        <button class="btn btn-sm btn-outline-primary" @click="next" :disabled="hasNext">älter</button>
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

<script>
import { Head } from '@vueuse/head'
import PackagePopularity from '../components/PackagePopularity'

export default {
  components: {
    Head,
    PackagePopularity
  },
  inject: ['apiService'],
  data () {
    return {
      query: this.$route.query.search ?? '',
      architecture: this.$route.query.architecture ?? '',
      repository: this.$route.query.repository ?? '',
      items: [],
      total: null,
      count: null,
      offset: 0,
      architectures: [],
      repositories: [],
      error: null,
      limit: 25
    }
  },
  watch: {
    query () {
      if (this.query.length > 255) {
        this.query = this.query.substring(0, 255)
      }

      this.query = this.query.replace(/(^[^a-zA-Z0-9]|[^a-zA-Z0-9@:.+_\- ]+)/, '')

      this.updateRoute()
      this.fetchPackages()
    },
    repository () {
      this.updateRoute()
      this.fetchPackages()
    },
    architecture () {
      this.updateRoute()
      this.fetchPackages()
    },
    offset () {
      this.fetchPackages()
    }
  },
  beforeRouteUpdate (to, from, next) {
    next()
    if (from.query.architecture !== to.query.architecture || from.query.repository !== to.query.repository) {
      this.$data.architecture = to.query.architecture
      this.$data.repository = to.query.repository
      this.fetchPackages()
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
    fetchPackages () {
      return this.apiService.fetchPackages({
        query: this.query,
        limit: this.limit,
        offset: this.offset,
        architecture: this.architecture,
        repository: this.repository
      })
        .then(data => {
          this.items = data.items
          this.total = data.total
          this.count = data.count
          this.offset = data.offset
          this.repositories = ['', ...data.repositories]
          this.architectures = ['', ...data.architectures]
          this.error = null
        })
        .catch(error => {
          this.items = []
          this.total = null
          this.count = null
          this.offset = 0
          this.repositories = []
          this.architectures = []
          this.error = error
        })
    },
    getQuery () {
      const query = {}
      if (this.$data.architecture) {
        query.architecture = this.$data.architecture
      }
      if (this.$data.repository) {
        query.repository = this.$data.repository
      }
      if (this.$data.query) {
        query.search = this.$data.query
      }
      return query
    },
    updateRoute () {
      const fromQuery = this.$route.query
      const toQuery = this.getQuery()
      if (fromQuery.architecture !== toQuery.architecture ||
        fromQuery.repository !== toQuery.repository ||
        fromQuery.search !== toQuery.search) {
        this.$router.replace({ query: toQuery })
      }
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
        name: 'packages',
        query: this.getQuery()
      }).href
    }
  },
  mounted () {
    this.fetchPackages()
  }
}
</script>
