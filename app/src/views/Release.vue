<template>
  <b-container role="main" tag="main">

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <template v-if="release.version">
      <h1 class="mb-4">Arch Linux {{ release.version }}</h1>
      <b-row>
        <b-col cols="12" xl="6">
          <h2 class="mb-3">Release Informationen</h2>
          <b-table-simple fixed small class="mb-4">
            <b-tr>
              <b-th>Version</b-th>
              <b-td>{{ release.version }}</b-td>
            </b-tr>
            <b-tr>
              <b-th>Verfügbarkeit</b-th>
              <b-td>
                <span v-if="release.available" class="text-success">✓</span>
                <span v-else class="text-danger">×</span>
              </b-td>
            </b-tr>
            <b-tr v-if="release.info">
              <b-th>Informationen</b-th>
              <b-td v-html="release.info"></b-td>
            </b-tr>
            <b-tr v-if="release.kernelVersion">
              <b-th>Kernel-Version</b-th>
              <b-td>{{ release.kernelVersion }}</b-td>
            </b-tr>
            <b-tr>
              <b-th>Veröffentlichung</b-th>
              <b-td>{{ (new Date(release.releaseDate)).toLocaleDateString('de-DE') }}</b-td>
            </b-tr>
            <b-tr v-if="release.fileSize">
              <b-th>ISO Größe</b-th>
              <b-td>{{ release.fileSize | prettyBytes(2, true) }}</b-td>
            </b-tr>
            <b-tr v-if="release.sha1Sum">
              <b-th>SHA1</b-th>
              <b-td class="text-break">{{ release.sha1Sum }}</b-td>
            </b-tr>
            <b-tr v-if="release.isoSigUrl">
              <b-th>PGP</b-th>
              <b-td><a :href="release.isoSigUrl" target="_blank" rel="nofollow noopener">PGP-Signatur</a>
              </b-td>
            </b-tr>
          </b-table-simple>
        </b-col>

        <b-col cols="12" xl="6">
          <a class="btn btn-primary btn-lg mb-4" target="_blank" :href="release.isoUrl" rel="nofollow noopener">
            <span class="font-weight-bold">Download</span> Arch Linux {{ release.version }}
          </a>
          <ul class="list-unstyled ml-4">
            <li v-if="release.magnetUri"><a :href="release.magnetUri" target="_blank" rel="nofollow noopener">Magnet link für {{ release.version }}</a></li>
            <li v-if="release.torrentUrl"><a :href="release.torrentUrl" target="_blank" rel="nofollow noopener">Torrent für {{ release.version }}</a></li>
            <li v-if="release.directoryUrl"><a :href="release.directoryUrl" target="_blank" rel="nofollow noopener">Verzeichnis für {{ release.version }}</a></li>
          </ul>
        </b-col>
      </b-row>

      <b-row>
        <b-col>
          <b-button :to="{name: 'releases'}" size="sm" variant="outline-secondary" exact>zum Release-Archiv</b-button>
        </b-col>
      </b-row>
    </template>
  </b-container>
</template>

<script>
export default {
  name: 'Release',
  metaInfo () {
    if (this.release.version) {
      return { title: this.release.version }
    } else {
      return {
        meta: [{ vmid: 'robots', name: 'robots', content: 'noindex,follow' }]
      }
    }
  },
  inject: ['apiService'],
  data () {
    return {
      release: {},
      error: ''
    }
  },
  methods: {
    fetchRelease () {
      this.apiService.fetchRelease(this.$route.params.version)
        .then(data => { this.release = data })
        .catch(error => { this.error = error })
    }
  },
  mounted () {
    this.fetchRelease()
  }
}
</script>
