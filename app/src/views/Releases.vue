<template>
  <b-container role="main" tag="main">
    <h1 class="mb-4">Arch Linux Releases</h1>

    <b-form-group>
      <b-form-input
        debounce="250"
        placeholder="Releases suchen"
        type="search"
        autocomplete="off"
        v-model="query"></b-form-input>
    </b-form-group>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-table striped responsive bordered small
             v-show="total > 0"
             :items="fetchReleases"
             :fields="fields"
             :filter="query"
             :per-page="perPage"
             :current-page="currentPage">

      <template v-slot:cell(version)="data">
        <router-link :to="{name: 'release', params: {version: data.value}}">{{ data.value }}</router-link>
      </template>

      <template v-slot:cell(releaseDate)="data">
        {{ (new Date(data.value)).toLocaleDateString('de-DE') }}
      </template>

      <template v-slot:cell(fileSize)="data">
        <span v-if="data.value">{{ data.value | prettyBytes(0, true) }}</span>
        <span v-else>-</span>
      </template>
    </b-table>

    <b-alert :show="total === 0" variant="warning">Keine Releases gefunden</b-alert>

    <b-row v-show="total > perPage">
      <b-col cols="12" sm="6" class="mb-3 text-right text-sm-left">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }}
      </b-col>
      <b-col cols="12" sm="6">
        <b-pagination
          v-model="currentPage"
          :total-rows="total"
          :per-page="perPage"
          :first-number="true"
          :last-number="true"
          align="right"
        ></b-pagination>
      </b-col>
    </b-row>

    <div class="mt-4">
      <a class="btn btn-outline-secondary btn-sm" href="/releases/feed">Feed</a>
      <b-button type="secondary" size="sm" :to="{name: 'download'}">Aktueller Download</b-button>
    </div>

  </b-container>
</template>

<script>
export default {
  name: 'Releases',
  metaInfo () {
    return {
      title: 'Arch Linux Releases',
      link: [{
        rel: 'canonical',
        href: window.location.origin + this.$router.resolve({
          name: 'releases',
          query: this.getQuery()
        }).href
      }],
      meta: [{ vmid: 'robots', name: 'robots', content: this.count < 1 ? 'noindex,follow' : 'index,follow' }]
    }
  },
  inject: ['apiService'],
  data () {
    return {
      fields: [{
        key: 'version',
        label: 'Version'
      }, {
        key: 'releaseDate',
        label: 'Datum'
      }, {
        key: 'kernelVersion',
        label: 'Kernel-Version',
        class: 'd-none d-xl-table-cell',
        thClass: 'text-nowrap'
      }, {
        key: 'fileSize',
        label: 'Größe',
        class: 'd-none d-md-table-cell'
      }],
      query: this.$route.query.search ?? '',
      perPage: 25,
      currentPage: 1,
      total: null,
      count: null,
      offset: null,
      error: ''
    }
  },
  watch: {
    query () {
      this.$router.replace({ query: this.getQuery() })
    }
  },
  methods: {
    fetchReleases (context) {
      return this.apiService.fetchReleases({
        query: context.filter,
        limit: context.perPage,
        offset: (context.currentPage - 1) * context.perPage
      })
        .then(data => {
          this.total = data.total
          this.count = data.count
          this.offset = data.offset
          this.error = ''
          return data.items
        })
        .catch(error => {
          this.total = 0
          this.count = 0
          this.offset = 0
          this.error = error
          return []
        })
    },
    getQuery () {
      const query = {}
      if (this.$data.query) {
        query.search = this.$data.query
      }
      return query
    }
  }
}
</script>
