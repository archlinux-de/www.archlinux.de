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
      this.fetchNews()
    },
    offset () {
      this.fetchNews()
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
    fetchNews () {
      return this.apiService.fetchNewsItems({
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
    createCanonical () {
      return window.location.origin + this.$router.resolve({
        name: 'news',
        query: this.getQuery()
      }).href
    }
  },
  mounted () {
    this.fetchNews()
  }
}
</script>
