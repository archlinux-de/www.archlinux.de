<template>
  <main class="container">
    <Head>
      <title v-if="release.version">{{ release.version }}</title>
      <link v-if="release.version" rel="canonical" :href="canonical">
      <meta v-if="release.version" name="description" :content="`Informationen und Download von Arch Linux Version ${release.version} mit Kernel ${release.kernelVersion}`">
      <meta v-if="!release.version" name="robots" content="noindex,follow">
    </Head>

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <template v-if="release.version">
      <h1 class="mb-4">Arch Linux {{ release.version }}</h1>
      <div class="row">
        <div class="col-12 col-xl-6">
          <h2 class="mb-3">Release Informationen</h2>
          <table class="table table-sm table-borderless mb-4">
            <tbody>
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
              <tr v-if="release.sha256Sum">
                <th>SHA256</th>
                <td class="text-break">{{ release.sha256Sum }}</td>
              </tr>
              <tr v-if="release.b2Sum">
                <th>B2</th>
                <td class="text-break">{{ release.b2Sum }}</td>
              </tr>
              <tr v-if="release.isoSigUrl">
                <th>PGP</th>
                <td><a class="p-0" :href="release.isoSigUrl" download rel="nofollow noopener">PGP-Signatur</a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="col-12 col-xl-6">
          <a class="btn btn-primary btn-lg mb-4" download :href="release.isoUrl" rel="nofollow noopener" data-test="release-download">
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

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Head } from '@vueuse/head'
import { useRouteParams } from '@vueuse/router'
import { useFetchRelease } from '~/composables/useFetchRelease'
import prettyBytes from 'pretty-bytes'

const router = useRouter()
const version = useRouteParams('version')

const { data: release, error } = useFetchRelease(version)

const canonical = computed(() => window.location.origin + router.resolve({ name: 'release', params: { version: release.value.version } }).href)
</script>
