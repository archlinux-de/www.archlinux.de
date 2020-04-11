<template>
  <b-container fluid role="main" tag="main">
    <h1 class="mb-4" v-if="pkg.name">{{ pkg.name }}</h1>

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <b-row v-if="pkg.name">
      <b-col cols="12" xl="6">
        <h2 class="mb-3">Paket-Details</h2>
        <table class="table table-sm">
          <colgroup>
            <col class="w-25">
            <col class="w-75">
          </colgroup>
          <tr>
            <th>Name</th>
            <td>{{ pkg.name }}</td>
          </tr>
          <tr>
            <th>Version</th>
            <td>{{ pkg.version }}</td>
          </tr>
          <tr>
            <th>Beschreibung</th>
            <td class="text-break">{{ pkg.description }}</td>
          </tr>
          <tr v-if="pkg.url">
            <th>URL</th>
            <td class="text-break">
              <a rel="nofollow" :href="pkg.url">{{ pkg.url }}</a>
            </td>
          </tr>
          <tr v-if="pkg.licenses && pkg.licenses.length > 0">
            <th>Lizenzen</th>
            <td>{{ pkg.licenses.join(', ') }}</td>
          </tr>
          <tr>
            <th>Repositorium</th>
            <td>
              <router-link
                :to="{name: 'packages', query: {architecture: pkg.repository.architecture, repository: pkg.repository.name}}">
                {{ pkg.repository.name }}
              </router-link>
            </td>
          </tr>
          <tr>
            <th>Architektur</th>
            <td>
              {{ pkg.architecture }}
            </td>
          </tr>
          <tr v-if="pkg.groups && pkg.groups.length > 0">
            <th>Gruppen</th>
            <td>{{ pkg.groups.join(', ') }}</td>
          </tr>
          <tr v-if="pkg.packager">
            <th>Packer</th>
            <td>
              <template v-if="pkg.packager.email">
                <a rel="nofollow" :href="'mailto:'+ pkg.packager.email">{{ pkg.packager.name }}</a>
              </template>
              <template v-else>{{ pkg.packager.name }}</template>
            </td>
          </tr>
          <tr>
            <th>Erstellt am</th>
            <td>{{ (new Date(pkg.buildDate)).toLocaleDateString('de-DE') }}</td>
          </tr>
          <tr>
            <th>Quelltext</th>
            <td><a :href="pkg.sourceUrl">Quelldateien</a>, <a :href="pkg.sourceChangelogUrl">Änderungshistorie</a></td>
          </tr>
          <tr>
            <th>Bugs</th>
            <td>
              <a :href="'https://bugs.archlinux.org/index.php?string=%5B'+ pkg.name +'%5D'">Bug-Tracker</a>
            </td>
          </tr>
          <tr>
            <th>Paket</th>
            <td>
              <a :href="pkg.packageUrl" target="_blank">{{ pkg.fileName }}</a>
            </td>
          </tr>
          <tr>
            <th>PGP-Signatur</th>
            <td>
              <a :href="pkg.packageUrl+'.sig'" target="_blank">{{ pkg.fileName }}.sig</a>
            </td>
          </tr>
          <tr>
            <th>Paket-Größe</th>
            <td>{{ pkg.compressedSize | prettyBytes }}</td>
          </tr>
          <tr>
            <th>Installations-Größe</th>
            <td>{{ pkg.installedSize | prettyBytes }}</td>
          </tr>
        </table>
      </b-col>

      <b-col cols="12" xl="6">
        <h2 class="mb-3 mt-5 mt-xl-0">Abhängigkeiten</h2>
        <b-row>
          <package-relations title="von" :relations="pkg.dependencies"></package-relations>
          <package-relations title="optional von" :relations="pkg.optionalDependencies"></package-relations>
          <package-relations title="stellt bereit" :relations="pkg.provisions"></package-relations>
          <package-relations title="ersetzt" :relations="pkg.replacements"></package-relations>
          <package-relations title="kollidiert mit" :relations="pkg.conflicts"></package-relations>
          <package-relations title="Bauen von" :relations="pkg.makeDependencies"></package-relations>
          <package-relations title="Test von" :relations="pkg.checkDependencies"></package-relations>

          <inverse-package-relations title="benötigt von" type="dependency"></inverse-package-relations>
          <inverse-package-relations title="optional für" type="optional-dependency"></inverse-package-relations>
          <!--          <inverse-package-relations title="Bereitgestellt von" type="provision"></inverse-package-relations>-->
          <!--          <inverse-package-relations title="Ersetzt von" type="replacement"></inverse-package-relations>-->
          <!--          <inverse-package-relations title="Kollision von" type="conflict"></inverse-package-relations>-->
          <inverse-package-relations title="Zum Bauen für" type="make-dependency"></inverse-package-relations>
          <inverse-package-relations title="Zum Testen für" type="check-dependency"></inverse-package-relations>
        </b-row>
      </b-col>

      <b-col cols="12">
        <h2 class="mb-3 mt-5 mt-xl-0">Dateien</h2>
        <b-button variant="outline-secondary" size="sm" class="ml-4"
                  v-if="files.length === 0" v-on:click.once="fetchFiles">
          Dateien anzeigen
        </b-button>
        <ul class="list-unstyled ml-4 overflow-auto">
          <li :key="key" v-for="(file, key) in files" :class="file.match(/\/$/) ? 'text-muted' : ''">{{ file }}</li>
        </ul>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
import PackageRelations from '@/js/components/PackageRelations'
import InversePackageRelations from '@/js/components/InversePackageRelations'

export default {
  name: 'Package',
  metaInfo () {
    if (this.pkg.name) {
      return {
        title: this.pkg.name,
        link: [{ rel: 'canonical', href: this.canonical }],
        meta: this.pkg.repository.testing
          ? [{ vmid: 'robots', name: 'robots', content: 'noindex' }]
          : [{ vmid: 'robots', name: 'robots', content: 'index,follow' }]
      }
    } else {
      return {
        meta: [{ vmid: 'robots', name: 'robots', content: 'noindex,follow' }]
      }
    }
  },
  components: {
    PackageRelations,
    InversePackageRelations
  },
  inject: ['apiService'],
  data () {
    return {
      pkg: {},
      files: [],
      canonical: '',
      error: ''
    }
  },
  methods: {
    fetchPackage () {
      this.apiService.fetchPackage(
        this.$route.params.repository,
        this.$route.params.architecture,
        this.$route.params.name)
        .then(data => {
          this.pkg = data
          this.files = []
          this.canonical = this.createCanonical()

          if (this.$route.fullPath !== this.canonical) {
            this.$router.replace(this.canonical)
          }
        })
        .catch(error => { this.error = error })
    },
    fetchFiles () {
      this.apiService.fetchPackageFiles(
        this.$route.params.repository,
        this.$route.params.architecture,
        this.$route.params.name)
        .then(data => {
          this.files = data.length ? data : ['Das Paket enthält keine Dateien']
        })
        .catch(error => { this.files = [error] })
    },
    createCanonical () {
      return this.$router.resolve({
        name: 'package',
        params: {
          repository: this.pkg.repository.name,
          architecture: this.pkg.repository.architecture,
          name: this.pkg.name
        }
      }).href
    }
  },
  mounted () {
    this.fetchPackage()
  },
  beforeRouteUpdate (to, from, next) {
    next()
    this.fetchPackage()
  }
}
</script>
