<template>
  <b-container fluid role="main" tag="main">
    <h1 class="mb-4">Paket-Suche</h1>

    <b-form-group>
      <b-form-input
        debounce="250"
        placeholder="Pakete suchen"
        type="search"
        autocomplete="off"
        v-model="query"></b-form-input>
    </b-form-group>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-table ref="table" striped responsive bordered small fixed
             v-show="total > 0"
             :items="fetchPackages"
             :fields="fields"
             :filter="query"
             :per-page="perPage"
             :current-page="currentPage">

      <template v-slot:cell(repository)="data">
        <router-link class="d-none d-lg-table-cell"
                     :to="{name: 'packages', query: {repository: data.value.name, architecture: data.value.architecture}}">
          {{ data.value.name }}
        </router-link>
      </template>

      <template v-slot:cell(name)="data">
        <router-link
          :to="{name: 'package', params: {repository: data.item.repository.name, architecture: data.item.repository.architecture, name: data.value}}">
          {{ data.value }}
        </router-link>
      </template>

      <template v-slot:cell(buildDate)="data">
        {{ (new Date(data.value)).toLocaleDateString('de-DE') }}
      </template>
    </b-table>

    <b-alert :show="total === 0" variant="warning">Keine Pakete gefunden</b-alert>

    <b-row v-show="total > perPage">
      <b-col cols="12" sm="6" class="mb-3 text-right text-sm-left">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }} Paketen
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

  </b-container>
</template>

<script>
export default {
  name: 'Packages',
  metaInfo () {
    return {
      title: 'Paket-Suche',
      link: [{
        rel: 'canonical',
        href: this.$router.resolve({
          name: 'packages',
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
        key: 'repository',
        label: 'Repositorium',
        class: 'd-none d-lg-table-cell'
      }, {
        key: 'architecture',
        label: 'Architektur',
        class: 'd-none d-xl-table-cell'
      }, {
        key: 'name',
        label: 'Name',
        tdClass: 'text-break'
      }, {
        key: 'version',
        label: 'Version',
        tdClass: 'text-break'
      }, {
        key: 'description',
        label: 'Beschreibung',
        class: 'd-none d-sm-table-cell',
        thClass: 'w-50',
        tdClass: 'text-break'
      }, {
        key: 'buildDate',
        label: 'Aktualisierung',
        class: 'd-none d-lg-table-cell'
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
      if (this.query.length > 255) {
        this.query = this.query.substring(0, 255)
      }

      this.query = this.query.replace(/(^[^a-zA-Z0-9]|[^a-zA-Z0-9@:.+_-]+)/, '')

      if (this.$route.query.search !== this.query) {
        this.$router.replace({ query: this.getQuery() })
      }
    }
  },
  beforeRouteUpdate (to, from, next) {
    next()
    if (from.query.architecture !== to.query.architecture || from.query.repository !== to.query.repository) {
      this.$refs.table.refresh()
    }
  },
  methods: {
    fetchPackages (context) {
      return this.apiService.fetchPackages({
        query: context.filter,
        limit: context.perPage,
        offset: (context.currentPage - 1) * context.perPage,
        architecture: this.$route.query.architecture,
        repository: this.$route.query.repository
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
      if (this.$route.query.architecture) {
        query.architecture = this.$route.query.architecture
      }
      if (this.$route.query.repository) {
        query.repository = this.$route.query.repository
      }
      if (this.$data.query) {
        query.search = this.$data.query
      }
      return query
    }
  }
}
</script>
