<template>
  <main class="container">
    <Head>
      <title>Download</title>
      <link rel="canonical" :href="canonical">
      <meta name="description" :content="'Arch Linux herunterladen und installieren in der aktuellen Version ' + release.version + ' mit Kernel ' + release.kernelVersion">
    </Head>
    <h1 class="mb-4">Arch Linux Downloads</h1>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <div class="row" v-if="release.version">
      <div class="col-12 col-lg-6">
        <h2>Release Informationen</h2>
        <p>Das CD/USB-Image ist gleichzeitig Installations-Medium und Live-System, das zur Wartung oder
          Reparatur
          benutzt werden kann. Die ISO-Datei kann sowohl auf CD gebrannt als auch mit Programmen wie
          <a href="https://wiki.archlinux.de/title/Dd">dd</a> auf USB-Sticks kopiert
          werden. Es kann nur für x86_64-Installationen verwendet werden.</p>
        <p>Der Download ist nur für Neuinstallationen notwendig! Ein bestehendes Arch Linux System kann immer
          mit
          <code>pacman -Syu</code> aktuell gehalten werden!</p>
        <ul class="list-unstyled ms-4">
          <li data-test="current-release"><strong>Aktuelles Release:</strong>&nbsp;<router-link
            :to="{name: 'release', params: {version: release.version}}">{{ release.version }}
          </router-link>
          </li>
          <li>
            <router-link :to="{name: 'release', params: {version: release.version}}">Release-Informationen</router-link>
          </li>
          <li><strong>Enthaltener Kernel:</strong>&nbsp;{{ release.kernelVersion }}</li>
          <li><strong>ISO Größe:</strong>&nbsp;{{ prettyBytes(release.fileSize, { locale: 'de', maximumFractionDigits: 2 }) }}</li>
          <li><a href="https://wiki.archlinux.de/title/Arch_Install_Scripts">Installations-Anleitung</a></li>
        </ul>

        <h2>Installation</h2>
        <p> Hilfe zur Erstellung der USB-Images und erste Schritte zum Aufsetzen des Basis-Systems findet man in
          der <a href="https://wiki.archlinux.de/title/Arch_Install_Scripts">Installations-Anleitung</a>.</p>
        <h2>BitTorrent Download</h2>
        <p><em>Ein web-seed-fähiger Client ist für schnelle Downloads zu empfehlen.</em></p>
        <ul class="list-unstyled ms-4 link-list">
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
          <table class="table table-sm table-borderless mb-4">
            <tbody>
              <tr v-if="release.isoSigUrl">
                <th>PGP</th>
                <td class="ps-2"><a class="p-0" :href="release.isoSigUrl" download rel="nofollow noopener">PGP-Signatur</a></td>
              </tr>
              <tr v-if="release.sha1Sum">
                <th>SHA1</th>
                <td class="ps-2 text-break">{{ release.sha1Sum }}</td>
              </tr>
              <tr v-if="release.sha256Sum">
                <th>SHA256</th>
                <td class="ps-2 text-break">{{ release.sha256Sum }}</td>
              </tr>
              <tr v-if="release.b2Sum">
                <th>B2</th>
                <td class="ps-2 text-break">{{ release.b2Sum }}</td>
              </tr>
            </tbody>
          </table>
      </div>

      <div class="col-12 col-lg-6 ps-lg-5">
        <a class="btn btn-primary btn-lg mb-4" download :href="release.isoUrl" data-test="download-release"
           rel="nofollow noopener">
          <span class="font-weight-bold">Download</span> Arch Linux {{ release.version }}
        </a>

        <template v-if="mirrors.items.length > 0">
          <h3>Mirrors</h3>
          <ul class="list-unstyled ms-4 link-list" data-test="mirror-list">
            <li :key="mirror.url" v-for="mirror in mirrors.items">
              <a :href="mirror.url + release.isoPath" download rel="nofollow noopener">{{ mirror.host }}</a>
            </li>
          </ul>
        </template>

        <h3>Arch Linux Releases</h3>
        <a class="btn btn-outline-secondary btn-sm" href="/releases/feed">Feed</a>
        <router-link :to="{name: 'releases'}" class="btn btn-secondary btn-sm">Archiv</router-link>
      </div>

      <component :is="'script'" type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "SoftwareApplication",
          "name": "Arch Linux",
          "operatingSystem": "Arch Linux",
          "fileSize": "{{ prettyBytes(release.fileSize, { maximumFractionDigits: 0 }) }}",
          "datePublished": "{{ (new Date(release.releaseDate)).toJSON() }}",
          "softwareVersion": "{{ release.version }}",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
          }
        }
      </component>
    </div>
  </main>
</template>

<script setup>
import { computed } from 'vue'
import prettyBytes from 'pretty-bytes'
import { Head } from '@vueuse/head'
import { useFetchReleases } from '~/composables/useFetchReleases'
import { useFetchMirrors } from '~/composables/useFetchMirrors'
import { useRouter } from 'vue-router'

const router = useRouter()

const { data: releases, error } = useFetchReleases({ limit: 1, onlyAvailable: true })
const release = computed(() => releases.value.items.length > 0 ? releases.value.items[0] : {})

const { data: mirrors } = useFetchMirrors({ limit: 10 })

const canonical = computed(() => window.location.origin + router.resolve({ name: 'download' }).href)
</script>
