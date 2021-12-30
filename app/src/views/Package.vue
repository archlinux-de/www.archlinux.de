<template>
  <main class="container">
    <h1 class="mb-4" v-if="pkg.name">{{ pkg.name }}</h1>
    <div class="row" v-if="pkg.name">
      <div class="col-12 col-xl-6">
        <h2 class="mb-3">Paket-Details</h2>
        <table class="table table-sm table-borderless">
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
              <a rel="nofollow noopener" :href="pkg.url">{{ pkg.url }}</a>
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
                <a rel="nofollow noopener" :href="'mailto:'+ pkg.packager.email">{{ pkg.packager.name }}</a>
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
            <td><a :href="pkg.sourceUrl">Quelldateien</a>, <a :href="pkg.sourceChangelogUrl">Änderungshistorie</a>
            </td>
          </tr>
          <tr>
            <th>Bugs</th>
            <td>
              <a :href="'https://bugs.archlinux.org/index.php?string=%5B'+ pkg.name +'%5D'"
                 rel="noopener">Bug-Tracker</a>
            </td>
          </tr>
          <tr>
            <th>Paket</th>
            <td>
              <a :href="pkg.packageUrl" download rel="nofollow noopener">{{ pkg.fileName }}</a>
            </td>
          </tr>
          <tr>
            <th>PGP-Signatur</th>
            <td>
              <a :href="pkg.packageUrl+'.sig'" download rel="nofollow noopener">{{ pkg.fileName }}.sig</a>
            </td>
          </tr>
          <tr>
            <th>Paket-Größe</th>
            <td>{{ pkg.compressedSize | prettyBytes(2, true) }}</td>
          </tr>
          <tr>
            <th>Installations-Größe</th>
            <td>{{ pkg.installedSize | prettyBytes(2, true) }}</td>
          </tr>
          <tr>
            <th>Beliebtheit</th>
            <td>
              <package-popularity :popularity="pkg.popularity"></package-popularity>
            </td>
          </tr>
        </table>
      </div>

      <div class="col-12 col-xl-6">
        <h2 class="mb-3 mt-5 mt-xl-0">Abhängigkeiten</h2>
        <div class="row">
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
        </div>
      </div>

      <div class="col-12">
        <h2 class="mb-3 mt-5 mt-xl-0">Dateien</h2>
        <package-files
          :repository="pkg.repository.name"
          :architecture="pkg.repository.architecture"
          :name="pkg.name"></package-files>
      </div>

      <script type="application/ld+json" v-if="!this.pkg.repository.testing && pkg.popularity.count > 0">
        {
          "@context": "https://schema.org",
          "@type": "SoftwareApplication",
          "name": "{{ pkg.name }}",
          "operatingSystem": "Arch Linux",
          "fileSize": "{{ pkg.compressedSize | prettyBytes(2, true) }}",
          "dateModified": "{{ (new Date(pkg.buildDate)).toJSON() }}",
          "softwareVersion": "{{ pkg.version }}",
          "description": "{{ pkg.description }}",
          "url": "{{ pkg.url }}",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
          },
          "aggregateRating": {
            "@type": "AggregateRating",
            "worstRating": 0,
            "bestRating": 100,
            "ratingCount": "{{ pkg.popularity.count }}",
            "ratingValue": "{{ pkg.popularity.popularity }}",
            "ratingExplanation": "The package {{ pkg.name }} got {{ pkg.popularity.count }} out of {{ pkg.popularity.samples }} votes submitted to pkgstats.",
            "url": "https://pkgstats.archlinux.de/packages/{{ pkg.name }}"
          }
        }
      </script>
    </div>

    <div class="row" v-if="error">
      <div class="col">
        <div class="card border-danger">
          <div class="card-header bg-danger text-white">Fehler beim Laden des Paket</div>
          <div class="card-body">
            <div class="card-text">
              Das Paket <strong>{{ $route.params.name }}</strong> aus <strong>[{{ $route.params.repository }}]</strong>
              konnte leider nicht angezeigt werden.
            </div>

            <table class="table table-borderless caption-top" v-if="suggestions.length > 0">
              <caption>Gefundene Vorschläge zu <strong>{{ $route.params.name }}</strong>:</caption>
              <tr :key="id" v-for="(suggestion, id) in suggestions">
                <td>
                  <router-link
                    :to="{name: 'package', params: {repository: suggestion.repository.name, architecture: suggestion.repository.architecture, name: suggestion.name}}">
                    {{ suggestion.name }}
                  </router-link>
                </td>
                <td> {{ suggestion.description }}</td>
                <td class="d-none d-lg-table-cell">
                  <package-popularity :popularity="suggestion.popularity"></package-popularity>
                </td>
              </tr>
            </table>
          </div>

          <div class="card-footer">
            <div class="d-flex justify-content-between">
              <small class="text-muted">{{ error }}</small>
              <small>
                <router-link :to="{name: 'packages', query: { search: $route.params.name }}">Nach {{
                    $route.params.name
                  }} suchen
                </router-link>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script>
import PackageRelations from '../components/PackageRelations'
import InversePackageRelations from '../components/InversePackageRelations'
import PackageFiles from '../components/PackageFiles'
import PackagePopularity from '../components/PackagePopularity'

export default {
  metaInfo () {
    const metaInfoObject = {}
    metaInfoObject.meta = []

    if (!this.pkg.name || this.pkg.repository.testing) {
      metaInfoObject.meta.push({ vmid: 'robots', name: 'robots', content: 'noindex' })
    }

    if (this.pkg.name) {
      metaInfoObject.title = this.pkg.name
      metaInfoObject.link = [{ rel: 'canonical', href: window.location.origin + this.canonical }]
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
            .then(data => {
              this.suggestions = data.items
            })
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
