<template>
  <b-container role="main" tag="main">
    <h1 class="mb-4">Arch Linux Downloads</h1>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-row v-if="release.version">
      <b-col cols="12" lg="6">
        <h2>Release Informationen</h2>
        <p>Das CD/USB-Image ist gleichzeitig Installations-Medium und Live-System, das zur Wartung oder
          Reparatur
          benutzt werden kann. Die ISO-Datei kann sowohl auf CD gebrannt als auch mit Programmen wie
          <a href="https://wiki.archlinux.de/title/Dd">dd</a> auf USB-Sticks kopiert
          werden. Es kann nur für x86_64-Installationen verwendet werden.</p>
        <p>Der Download ist nur für Neuinstallationen notwendig! Ein bestehendes Arch Linux System kann immer
          mit
          <code>pacman -Syu</code> aktuell gehalten werden!</p>
        <ul class="list-unstyled ml-4">
          <li data-test="current-release"><strong>Aktuelles Release:</strong>&nbsp;<router-link
            :to="{name: 'release', params: {version: release.version}}">{{ release.version }}
          </router-link>
          </li>
          <li>
            <router-link :to="{name: 'release', params: {version: release.version}}">Release-Informationen</router-link>
          </li>
          <li><strong>Enthaltener Kernel:</strong>&nbsp;{{ release.kernelVersion }}</li>
          <li><strong>ISO Größe:</strong>&nbsp;{{ release.fileSize | prettyBytes(2, true) }}</li>
          <li><a href="https://wiki.archlinux.de/title/Arch_Install_Scripts">Installations-Anleitung</a></li>
        </ul>

        <h2>Installation</h2>
        <p> Hilfe zur Erstellung der USB-Images und erste Schritte zum Aufsetzen des Basis-Systems findet man in
          der <a href="https://wiki.archlinux.de/title/Arch_Install_Scripts">Installations-Anleitung</a>.</p>
        <h2>BitTorrent Download</h2>
        <p><em>Ein web-seed-fähiger Client ist für schnelle Downloads zu empfehlen.</em></p>
        <ul class="list-unstyled ml-4 link-list">
          <li><a :href="release.magnetUri" download rel="nofollow noopener">Magnet link für {{
              release.version
            }}</a></li>
          <li><a :href="release.torrentUrl" download rel="nofollow noopener">
            Torrent für {{ release.version }}
          </a></li>
        </ul>

        <h2>Direkte HTTP-Downloads</h2>
        <p>Nach dem Download sollten die Dateien stets überprüft werden.</p>

        <h3>Prüfsummen</h3>
        <ul class="list-unstyled ml-4">
          <li><a :href="release.isoSigUrl" download rel="nofollow noopener">PGP-Signatur</a></li>
          <li class="text-break" v-if="release.sha1Sum"><strong>SHA1:</strong> {{ release.sha1Sum }}</li>
        </ul>
      </b-col>

      <b-col class="pl-lg-5" cols="12" lg="6">
        <a class="btn btn-primary btn-lg mb-4" download :href="release.isoUrl" data-test="download-release"
           rel="nofollow noopener">
          <span class="font-weight-bold">Download</span> Arch Linux {{ release.version }}
        </a>

        <template v-if="mirrors.length > 0">
          <h3>Mirrors</h3>
          <ul class="list-unstyled ml-4 link-list" data-test="mirror-list">
            <li :key="mirror.url" v-for="mirror in mirrors">
              <a :href="mirror.url + release.isoPath" download rel="nofollow noopener">{{ mirror.host }}</a>
            </li>
          </ul>
        </template>

        <h3>Arch Linux Releases</h3>
        <a class="btn btn-outline-secondary btn-sm" href="/releases/feed">Feed</a>
        <router-link :to="{name: 'releases'}" class="btn btn-secondary btn-sm" role="button">Archiv</router-link>
      </b-col>

      <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "SoftwareApplication",
          "name": "Arch Linux",
          "operatingSystem": "Arch Linux",
          "fileSize": "{{ release.fileSize | prettyBytes(2, true) }}",
          "datePublished": "{{ (new Date(release.releaseDate)).toJSON() }}",
          "softwareVersion": "{{ release.version }}",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
          }
        }
      </script>
    </b-row>
  </b-container>
</template>

<script>
export default {
  name: 'Download',
  metaInfo () {
    return {
      title: 'Download',
      link: [{ rel: 'canonical', href: window.location.origin + this.$router.resolve({ name: 'download' }).href }],
      meta: [{
        name: 'description',
        content: `Arch Linux herunterladen und installieren in der aktuellen Version ${this.release.version} mit Kernel ${this.release.kernelVersion}`
      }]
    }
  },
  inject: ['apiService'],
  data () {
    return {
      release: {},
      mirrors: [],
      error: ''
    }
  },
  methods: {
    fetchLatestRelease () {
      this.apiService.fetchReleases({ limit: 1, onlyAvailable: true })
        .then(data => {
          this.release = data.items[0]
        })
        .catch(error => {
          this.error = error
        })
    },
    fetchMirrors () {
      this.apiService.fetchMirrors({ limit: 10 })
        .then(data => {
          this.mirrors = data.items
        })
        .catch(() => {
        })
    }
  },
  mounted () {
    this.fetchLatestRelease()
    this.fetchMirrors()
  }
}
</script>
