<template>
  <b-container fluid role="main" tag="main">
    <h1 class="mb-4">Neuigkeiten</h1>

    <b-form-group>
      <b-form-input
        debounce="250"
        placeholder="Neuigkeiten suchen"
        type="search"
        autocomplete="off"
        v-model="query"></b-form-input>
    </b-form-group>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-table striped responsive bordered small
             v-show="total > 0"
             :items="fetchNews"
             :fields="fields"
             :filter="query"
             :per-page="perPage"
             :current-page="currentPage">

      <template v-slot:cell(lastModified)="data">
        {{ (new Date(data.value)).toLocaleDateString('de-DE') }}
      </template>

      <template v-slot:cell(title)="data">
        <router-link :to="{name: 'news-item', params: {id: data.item.id, slug: data.item.slug}}">
          {{ data.value }}
        </router-link>
      </template>

      <template v-slot:cell(author)="data">
        <a :href="data.value.uri">{{ data.value.name }}</a>
      </template>
    </b-table>

    <b-alert :show="total === 0" variant="warning">Keine Neuigkeiten gefunden</b-alert>

    <b-row v-show="total > perPage">
      <b-col cols="12" sm="6" class="mb-3 text-right text-sm-left">
        {{ offset + 1 }} bis {{ offset + count }} von {{ total }} Neuigkeiten
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
  name: 'News',
  metaInfo () {
    return {
      title: 'Neuigkeiten',
      link: [{
        rel: 'canonical',
        href: this.$router.resolve({
          name: 'news',
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
        key: 'lastModified',
        label: 'Veröffentlichung',
        class: 'd-none d-md-table-cell'
      }, {
        key: 'title',
        label: 'Titel'
      }, {
        key: 'author',
        label: 'Autor',
        class: 'd-none d-xl-table-cell'
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
    fetchNews (context) {
      return this.apiService.fetchNewsItems({
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
