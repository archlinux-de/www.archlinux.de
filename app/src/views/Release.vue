<template>
  <main class="container">
    <Head>
      <title v-if="release.version">{{ release.version }} - archlinux.de</title>
      <link v-if="release.version" rel="canonical" :href="createCanonical()">
      <meta v-if="release.version" name="description" :content="`Informationen und Download von Arch Linux Version ${release.version} mit Kernel ${release.kernelVersion}`">
      <meta v-if="!release.version" name="robots" content="noindex,follow">
    </Head>

    <div class="alert alert-danger" v-show="error != ''">{{ error }}</div>

    <template v-if="release.version">
      <h1 class="mb-4">Arch Linux {{ release.version }}</h1>
      <div class="row">
        <div class="col-12 col-xl-6">
          <h2 class="mb-3">Release Informationen</h2>
          <table class="table table-sm table-borderless mb-4">
            <tr>
              <th>Version</th>
              <td>{{ release.version }}</td>
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
              <td>{{ prettyBytes(release.fileSize, { locale: 'de', maximumFractionDigits: 2 }) }}</td>
            </tr>
            <tr v-if="release.sha1Sum">
              <th>SHA1</th>
              <td class="text-break">{{ release.sha1Sum }}</td>
            </tr>
            <tr v-if="release.isoSigUrl">
              <th>PGP</th>
              <td><a :href="release.isoSigUrl" download rel="nofollow noopener">PGP-Signatur</a>
              </td>
            </tr>
          </table>
        </div>

        <div class="col-12 col-xl-6">
          <a class="btn btn-primary btn-lg mb-4" download :href="release.isoUrl" rel="nofollow noopener">
            <span class="font-weight-bold">Download</span> Arch Linux {{ release.version }}
          </a>
          <ul class="list-unstyled ms-4 link-list">
            <li v-if="release.magnetUri"><a :href="release.magnetUri" download rel="nofollow noopener">Magnet link für {{ release.version }}</a></li>
            <li v-if="release.torrentUrl"><a :href="release.torrentUrl" download rel="nofollow noopener">Torrent für {{ release.version }}</a></li>
            <li v-if="release.directoryUrl"><a :href="release.directoryUrl" target="_blank" rel="nofollow noopener">Verzeichnis für {{ release.version }}</a></li>
          </ul>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <router-link class="btn btn-sm btn-outline-secondary" :to="{name: 'releases'}" exact>zum Release-Archiv</router-link>
        </div>
      </div>
    </template>
  </main>
</template>

<script>
import { Head } from '@vueuse/head'
import prettyBytes from 'pretty-bytes'

export default {
  components: {
    Head
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
    },
    prettyBytes,
    createCanonical () {
      return window.location.origin + this.$router.resolve({
        name: 'release',
        params: {
          version: this.release.version
        }
      }).href
    }
  },
  mounted () {
    this.fetchRelease()
  }
}
</script>
