<template>
  <b-container fluid role="main" tag="main">
    <h1 class="mb-4">Paket-Suche</h1>

    <b-input-group class="mb-2">
      <b-form-input
        debounce="250"
        trim
        placeholder="Pakete suchen"
        type="search"
        autocomplete="off"
        v-model="currentQuery"></b-form-input>

      <b-input-group-append>
        <b-form-select v-model="currentRepository" :options="repositories"></b-form-select>
        <b-form-select v-if="currentArchitecture" v-model="currentArchitecture" :options="architectures"
                       class="d-none d-sm-block"></b-form-select>
      </b-input-group-append>
    </b-input-group>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-table ref="table" striped responsive bordered small fixed
             v-show="total > 0"
             :items="fetchPackages"
             :fields="fields"
             :filter="currentQuery"
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
      currentQuery: this.$route.query.search ?? '',
      currentArchitecture: this.$route.query.architecture ?? '',
      currentRepository: this.$route.query.repository ?? '',
      perPage: 25,
      currentPage: 1,
      total: null,
      count: null,
      offset: null,
      architectures: [],
      repositories: [],
      error: ''
    }
  },
  watch: {
    currentQuery () {
      if (this.currentQuery.length > 255) {
        this.currentQuery = this.currentQuery.substring(0, 255)
      }

      this.currentQuery = this.currentQuery.replace(/(^[^a-zA-Z0-9]|[^a-zA-Z0-9@:.+_\- ]+)/, '')

      this.updateRoute()
    },
    currentRepository () {
      this.updateRoute()
    },
    currentArchitecture () {
      this.updateRoute()
    }
  },
  beforeRouteUpdate (to, from, next) {
    next()
    if (from.query.architecture !== to.query.architecture || from.query.repository !== to.query.repository) {
      this.$data.currentArchitecture = to.query.architecture
      this.$data.currentRepository = to.query.repository
      this.$refs.table.refresh()
    }
  },
  methods: {
    fetchPackages (context) {
      return this.apiService.fetchPackages({
        query: context.filter,
        limit: context.perPage,
        offset: (context.currentPage - 1) * context.perPage,
        architecture: this.currentArchitecture,
        repository: this.currentRepository
      })
        .then(data => {
          this.total = data.total
          this.count = data.count
          this.offset = data.offset
          this.repositories = ['', ...data.repositories]
          this.architectures = ['', ...data.architectures]
          this.error = ''
          return data.items
        })
        .catch(error => {
          this.total = 0
          this.count = 0
          this.offset = 0
          this.repositories = []
          this.architectures = []
          this.error = error
          return []
        })
    },
    getQuery () {
      const query = {}
      if (this.$data.currentArchitecture) {
        query.architecture = this.$data.currentArchitecture
      }
      if (this.$data.currentRepository) {
        query.repository = this.$data.currentRepository
      }
      if (this.$data.currentQuery) {
        query.search = this.$data.currentQuery
      }
      return query
    },
    updateRoute () {
      const fromQuery = this.$route.query
      const toQuery = this.getQuery()
      if (JSON.stringify(fromQuery) !== JSON.stringify(toQuery)) {
        this.$router.replace({ query: toQuery })
      }
    }
  }
}
</script>
