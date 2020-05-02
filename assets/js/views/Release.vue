<template>
  <b-container role="main" fluid tag="main">

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <template v-if="release.version">
      <h1 class="mb-4">Arch Linux {{ release.version }}</h1>
      <b-row>
        <b-col cols="12" xl="6">
          <h2 class="mb-3">Release Informationen</h2>
          <table class="table table-sm mb-4">
            <colgroup>
              <col class="w-25">
              <col class="w-75">
            </colgroup>
            <tr>
              <th>Version</th>
              <td>{{ release.version }}</td>
            </tr>
            <tr>
              <th>Verfügbarkeit</th>
              <td>
                <span v-if="release.available" class="text-success">✓</span>
                <span v-else class="text-danger">×</span>
              </td>
            </tr>
            <tr v-if="release.info">
              <th>Informationen</th>
              <td v-html="release.info"></td>
            </tr>
            <tr v-if="release.kernelVersion">
              <th>Kernel-Version</th>
              <td>{{ release.kernelVersion }}</td>
            </tr>
            <tr>
              <th>Veröffentlichung</th>
              <td>{{ (new Date(release.releaseDate)).toLocaleDateString('de-DE') }}</td>
            </tr>
            <tr v-if="release.fileSize">
              <th>ISO Größe</th>
              <td>{{ release.fileSize | prettyBytes }}</td>
            </tr>
            <tr v-if="release.sha1Sum">
              <th>SHA1</th>
              <td class="text-break">{{ release.sha1Sum }}</td>
            </tr>
            <tr v-if="release.available">
              <th>PGP</th>
              <td><a :href="release.isoSigUrl" target="_blank">PGP-Signatur</a>
              </td>
            </tr>
          </table>
        </b-col>

        <b-col cols="12" xl="6">
          <a v-if="release.available" class="btn btn-primary btn-lg mb-4" target="_blank" :href="release.isoUrl">
            <span class="font-weight-bold">Download</span> Arch Linux {{ release.version }}
          </a>
          <ul class="list-unstyled ml-4" v-if="release.torrentUrl">
            <li><a :href="release.magnetUri" target="_blank">Magnet link für {{ release.version }}</a></li>
            <li><a :href="release.torrentUrl" target="_blank">Torrent für {{ release.version }}</a></li>
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
