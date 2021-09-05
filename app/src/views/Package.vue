<template>
  <b-container role="main" tag="main">
    <h1 class="mb-4" v-if="pkg.name">{{ pkg.name }}</h1>
    <b-row v-if="pkg.name">
      <b-col cols="12" xl="6">
        <h2 class="mb-3">Paket-Details</h2>
        <b-table-simple small fixed>
          <b-tr>
            <b-th>Name</b-th>
            <b-td>{{ pkg.name }}</b-td>
          </b-tr>
          <b-tr>
            <b-th>Version</b-th>
            <b-td>{{ pkg.version }}</b-td>
          </b-tr>
          <b-tr>
            <b-th>Beschreibung</b-th>
            <b-td class="text-break">{{ pkg.description }}</b-td>
          </b-tr>
          <b-tr v-if="pkg.url">
            <b-th>URL</b-th>
            <b-td class="text-break">
              <a rel="nofollow noopener" :href="pkg.url">{{ pkg.url }}</a>
            </b-td>
          </b-tr>
          <b-tr v-if="pkg.licenses && pkg.licenses.length > 0">
            <b-th>Lizenzen</b-th>
            <b-td>{{ pkg.licenses.join(', ') }}</b-td>
          </b-tr>
          <b-tr>
            <b-th>Repositorium</b-th>
            <b-td>
              <router-link
                :to="{name: 'packages', query: {architecture: pkg.repository.architecture, repository: pkg.repository.name}}">
                {{ pkg.repository.name }}
              </router-link>
            </b-td>
          </b-tr>
          <b-tr>
            <b-th>Architektur</b-th>
            <b-td>
              {{ pkg.architecture }}
            </b-td>
          </b-tr>
          <b-tr v-if="pkg.groups && pkg.groups.length > 0">
            <b-th>Gruppen</b-th>
            <b-td>{{ pkg.groups.join(', ') }}</b-td>
          </b-tr>
          <b-tr v-if="pkg.packager">
            <b-th>Packer</b-th>
            <b-td>
              <template v-if="pkg.packager.email">
                <a rel="nofollow noopener" :href="'mailto:'+ pkg.packager.email">{{ pkg.packager.name }}</a>
              </template>
              <template v-else>{{ pkg.packager.name }}</template>
            </b-td>
          </b-tr>
          <b-tr>
            <b-th>Erstellt am</b-th>
            <b-td>{{ (new Date(pkg.buildDate)).toLocaleDateString('de-DE') }}</b-td>
          </b-tr>
          <b-tr>
            <b-th>Quelltext</b-th>
            <b-td><a :href="pkg.sourceUrl">Quelldateien</a>, <a :href="pkg.sourceChangelogUrl">Änderungshistorie</a>
            </b-td>
          </b-tr>
          <b-tr>
            <b-th>Bugs</b-th>
            <b-td>
              <a :href="'https://bugs.archlinux.org/index.php?string=%5B'+ pkg.name +'%5D'"
                 rel="noopener">Bug-Tracker</a>
            </b-td>
          </b-tr>
          <b-tr>
            <b-th>Paket</b-th>
            <b-td>
              <a :href="pkg.packageUrl" target="_blank" rel="nofollow noopener">{{ pkg.fileName }}</a>
            </b-td>
          </b-tr>
          <b-tr>
            <b-th>PGP-Signatur</b-th>
            <b-td>
              <a :href="pkg.packageUrl+'.sig'" target="_blank" rel="nofollow noopener">{{ pkg.fileName }}.sig</a>
            </b-td>
          </b-tr>
          <b-tr>
            <b-th>Paket-Größe</b-th>
            <b-td>{{ pkg.compressedSize | prettyBytes(2, true) }}</b-td>
          </b-tr>
          <b-tr>
            <b-th>Installations-Größe</b-th>
            <b-td>{{ pkg.installedSize | prettyBytes(2, true) }}</b-td>
          </b-tr>
          <b-tr>
            <b-th>Beliebtheit</b-th>
            <b-td>
              <package-popularity :popularity="pkg.popularity"></package-popularity>
            </b-td>
          </b-tr>
        </b-table-simple>
      </b-col>

      <b-col cols="12" xl="6">
        <h2 class="mb-3 mt-5 mt-xl-0">Abhängigkeiten</h2>
        <b-row>
          <package-relations
            v-for="relation in relations"
            :key="'package-relations-' + relation.type"
            :repository="pkg.repository.name"
            :architecture="pkg.repository.architecture"
            :name="pkg.name"
            :type="relation.type"
            :title="relation.title"></package-relations>

          <inverse-package-relations
            v-for="inverseRelation in inverseRelations"
            :key="'inverse-package-relations-' + inverseRelation.type"
            :repository="pkg.repository.name"
            :architecture="pkg.repository.architecture"
            :name="pkg.name"
            :type="inverseRelation.type"
            :title="inverseRelation.title"></inverse-package-relations>
        </b-row>
      </b-col>

      <b-col cols="12">
        <h2 class="mb-3 mt-5 mt-xl-0">Dateien</h2>
        <package-files
          :repository="pkg.repository.name"
          :architecture="pkg.repository.architecture"
          :name="pkg.name"></package-files>
      </b-col>
    </b-row>

    <b-row v-if="error">
      <b-col>
        <b-card border-variant="danger" header-bg-variant="danger" header-text-variant="white"
                header="Fehler beim Laden des Paket">
          <b-card-text>
            Das Paket <strong>{{ $route.params.name }}</strong> aus <strong>[{{ $route.params.repository }}]</strong>
            konnte leider nicht angezeigt werden.
          </b-card-text>

          <b-table-simple v-if="suggestions.length > 0" caption-top>
            <caption>Gefundene Vorschläge zu <strong>{{ $route.params.name }}</strong>:</caption>
            <b-tr :key="id" v-for="(suggestion, id) in suggestions">
              <b-td>
                <router-link
                  :to="{name: 'package', params: {repository: suggestion.repository.name, architecture: suggestion.repository.architecture, name: suggestion.name}}">
                  {{ suggestion.name }}
                </router-link>
              </b-td>
              <b-td> {{ suggestion.description }}</b-td>
              <b-td class="d-none d-lg-table-cell">
                <package-popularity :popularity="suggestion.popularity"></package-popularity>
              </b-td>
            </b-tr>
          </b-table-simple>

          <template #footer>
            <div class="d-flex justify-content-between">
              <small class="text-muted">{{ error }}</small>
              <small>
                <router-link :to="{name: 'packages', query: { search: $route.params.name }}">Nach {{ $route.params.name }} suchen</router-link>
              </small>
            </div>
          </template>
        </b-card>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
import PackageRelations from '../components/PackageRelations'
import InversePackageRelations from '../components/InversePackageRelations'
import PackageFiles from '../components/PackageFiles'
import PackagePopularity from '../components/PackagePopularity'

export default {
  name: 'Package',
  metaInfo () {
    const metaInfoObject = {}
    metaInfoObject.meta = []

    if (!this.pkg.name || this.pkg.repository.testing) {
      metaInfoObject.meta.push({ vmid: 'robots', name: 'robots', content: 'noindex' })
    }

    if (this.pkg.name) {
      metaInfoObject.title = this.pkg.name
      metaInfoObject.link = [{ rel: 'canonical', href: this.canonical }]
      metaInfoObject.meta.push({ vmid: 'description', name: 'description', content: this.pkg.description })
    }

    return metaInfoObject
  },
  components: {
    PackagePopularity,
    PackageRelations,
    InversePackageRelations,
    PackageFiles
  },
  inject: ['apiService'],
  data () {
    return {
      relations: [{
        title: 'benötigt',
        type: 'dependency'
      }, {
        title: 'optional',
        type: 'optional-dependency'
      }, {
        title: 'stellt bereit',
        type: 'provision'
      }, {
        title: 'ersetzt',
        type: 'replacement'
      }, {
        title: 'kollidiert mit',
        type: 'conflict'
      }, {
        title: 'zum bauen',
        type: 'make-dependency'
      }, {
        title: 'zum testen',
        type: 'check-dependency'
      }],
      inverseRelations: [{
        title: 'benötigt von',
        type: 'dependency'
      }, {
        title: 'optional für',
        type: 'optional-dependency'
      }, {
        title: 'bauen für',
        type: 'make-dependency'
      }, {
        title: 'testen für',
        type: 'check-dependency'
      }],
      pkg: {},
      canonical: '',
      error: '',
      suggestions: []
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
        .catch(error => {
          this.pkg = {}
          this.files = []
          this.canonical = ''
          this.error = error
          this.apiService.fetchPackages({
            query: this.$route.params.name,
            limit: 5
          })
            .then(data => { this.suggestions = data.items })
        })
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
    this.error = ''
    this.suggestions = ''
  }
}
</script>
