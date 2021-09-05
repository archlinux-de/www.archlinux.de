<template>
  <b-container role="main" tag="main">
    <h1 class="mb-4">Mirror-Status</h1>

    <b-form-group>
      <b-form-input
        debounce="250"
        placeholder="Mirror suchen"
        type="search"
        autocomplete="off"
        v-model="query"></b-form-input>
    </b-form-group>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-table striped responsive bordered small
             v-show="total > 0"
             :items="fetchMirrors"
             :fields="fields"
             :filter="query"
             :per-page="perPage"
             :current-page="currentPage">

      <template v-slot:cell(url)="data">
        <a :href="data.value" rel="nofollow noopener" target="_blank">{{ data.item.host }}</a>
      </template>

      <template v-slot:cell(durationAvg)="data">
        {{ renderDuration(data.value) }}
      </template>

      <template v-slot:cell(delay)="data">
        {{ renderDuration(data.value) }}
      </template>

      <template v-slot:cell(lastSync)="data">
        {{ (new Date(data.value)).toLocaleDateString('de-DE') }}
      </template>

      <template v-slot:cell(ipv4)="data">
        <span v-if="data.value" class="text-success">✓</span>
        <span v-else class="text-danger">×</span>
      </template>

      <template v-slot:cell(ipv6)="data">
        <span v-if="data.value" class="text-success">✓</span>
        <span v-else class="text-danger">×</span>
      </template>
    </b-table>

    <b-alert :show="total === 0" variant="warning">Keine Mirror gefunden</b-alert>

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

  </b-container>
</template>

<script>
export default {
  name: 'Mirrors',
  metaInfo () {
    return {
      title: 'Mirror-Status',
      link: [{
        rel: 'canonical',
        href: window.location.origin + this.$router.resolve({
          name: 'mirrors',
          query: this.getQuery()
        }).href
      }],
      meta: [
        { vmid: 'robots', name: 'robots', content: this.count < 1 ? 'noindex,follow' : 'index,follow' },
        { name: 'description', content: this.getQuery().search ? `${this.getQuery().search}-Mirror für Arch Linux` : 'Paket-Mirror Arch Linux' }
      ]
    }
  },
  inject: ['apiService'],
  data () {
    return {
      fields: [{
        key: 'url',
        label: 'URL'
      }, {
        key: 'country.name',
        label: 'Land',
        class: 'd-none d-md-table-cell'
      }, {
        key: 'durationAvg',
        label: '∅ Antwortzeit',
        class: 'd-none d-lg-table-cell',
        thClass: 'text-nowrap'
      }, {
        key: 'delay',
        label: '∅ Verzögerung',
        class: 'd-none d-lg-table-cell',
        thClass: 'text-nowrap'
      }, {
        key: 'lastSync',
        label: 'Datum',
        class: 'd-none d-sm-table-cell'
      }, {
        key: 'ipv4',
        label: 'IPv4',
        class: 'd-none d-xl-table-cell text-center'
      }, {
        key: 'ipv6',
        label: 'IPv6',
        class: 'd-none d-md-table-cell text-center'
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
    fetchMirrors (context) {
      return this.apiService.fetchMirrors({
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
    }
  }
}
</script>
