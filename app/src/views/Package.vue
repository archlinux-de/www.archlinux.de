<template>
  <main class="container">
    <Head>
      <title v-if="pkg.name">{{ pkg.name }}</title>
      <link v-if="pkg.name" rel="canonical" :href="canonical">
      <meta v-if="pkg.description" name="description" :content="pkg.description">
      <meta name="robots" content="noindex" v-if="!pkg.name || pkg.repository.testing">
    </Head>

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
              <a class="p-0" rel="nofollow noopener" :href="pkg.url">{{ pkg.url }}</a>
            </td>
          </tr>
          <tr v-if="pkg.licenses && pkg.licenses.length > 0">
            <th>Lizenzen</th>
            <td>{{ pkg.licenses.join(', ') }}</td>
          </tr>
          <tr>
            <th>Repositorium</th>
            <td>
              <router-link class="p-0"
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
                <a class="p-0" rel="nofollow noopener" :href="'mailto:'+ pkg.packager.email">{{ pkg.packager.name }}</a>
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
            <td><a class="p-0" :href="pkg.sourceUrl" rel="nofollow noopener" target="_blank">Quelldateien</a>, <a class="p-0" :href="pkg.sourceChangelogUrl" rel="nofollow noopener" target="_blank">Änderungshistorie</a>
            </td>
          </tr>
          <tr v-if="pkg.issueUrl">
            <th>Bugs</th>
            <td>
              <a class="p-0" :href="pkg.issueUrl" rel="noopener">Issue-Tracker</a>
            </td>
          </tr>
          <tr>
            <th>Paket</th>
            <td>
              <a class="p-0" :href="pkg.packageUrl" download rel="nofollow noopener">{{ pkg.fileName }}</a>
            </td>
          </tr>
          <tr>
            <th>PGP-Signatur</th>
            <td>
              <a class="p-0" :href="pkg.packageUrl+'.sig'" download rel="nofollow noopener">{{ pkg.fileName }}.sig</a>
            </td>
          </tr>
          <tr>
            <th>Paket-Größe</th>
            <td>{{ prettyBytes(pkg.compressedSize, { locale: 'de', maximumFractionDigits: 2 }) }}</td>
          </tr>
          <tr>
            <th>Installations-Größe</th>
            <td>{{ prettyBytes(pkg.installedSize, { locale: 'de', maximumFractionDigits: 2 }) }}</td>
          </tr>
          <tr>
            <th>Beliebtheit</th>
            <td>
              <package-popularity class="p-0" :popularity="pkg.popularity"></package-popularity>
            </td>
          </tr>
        </table>
      </div>

      <div class="col-12 col-xl-6">
        <h2 class="mb-3 mt-5 mt-xl-0">Abhängigkeiten</h2>
        <div class="row">
          <package-relations
            v-for="relation in relations"
            :key="'package-relations-' + relation.type + '-' + canonical"
            :repository="pkg.repository.name"
            :architecture="pkg.repository.architecture"
            :name="pkg.name"
            :type="relation.type"
            :title="relation.title"></package-relations>

          <inverse-package-relations
            v-for="inverseRelation in inverseRelations"
            :key="'inverse-package-relations-' + inverseRelation.type + '-' + canonical"
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
          :key="'package-files-' + canonical"
          :repository="pkg.repository.name"
          :architecture="pkg.repository.architecture"
          :name="pkg.name"></package-files>
      </div>

      <component :is="'script'" type="application/ld+json" v-if="!pkg.repository.testing && pkg.popularity.count > 0">
        {
          "@context": "https://schema.org",
          "@type": "SoftwareApplication",
          "name": "{{ pkg.name }}",
          "operatingSystem": "Arch Linux",
          "fileSize": "{{ prettyBytes(pkg.compressedSize, { maximumFractionDigits: 0 }) }}",
          "dateModified": "{{ (new Date(pkg.buildDate)).toJSON() }}",
          "softwareVersion": "{{ pkg.version }}",
          "description": {{ JSON.stringify(pkg.description) }},
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
      </component>
    </div>

    <div class="row" v-if="error">
      <div class="col">
        <div class="card border-danger">
          <div class="card-header bg-danger text-white">Fehler beim Laden des Paket</div>
          <div class="card-body">
            <div class="card-text">
              Das Paket <strong>{{ name }}</strong> aus <strong>[{{ repository }}]</strong>
              konnte leider nicht angezeigt werden.
            </div>
            <package-suggestions :name="name" :limit="5"></package-suggestions>
          </div>

          <div class="card-footer">
            <div class="d-flex justify-content-between">
              <small class="text-muted">{{ error }}</small>
              <small>
                <router-link :to="{name: 'packages', query: { search: name }}">Nach {{
                    name
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

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Head } from '@vueuse/head'
import prettyBytes from 'pretty-bytes'
import PackageRelations from '~/components/PackageRelations'
import InversePackageRelations from '~/components/InversePackageRelations'
import PackageFiles from '~/components/PackageFiles'
import PackagePopularity from '~/components/PackagePopularity'
import PackageSuggestions from '~/components/PackageSuggestions'
import { useFetchPackage } from '~/composables/useFetchPackage'
import { useRouteParams } from '@vueuse/router'

const router = useRouter()

const relations = [{
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
}]

const inverseRelations = [{
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
}]

const repository = useRouteParams('repository')
const architecture = useRouteParams('architecture')
const name = useRouteParams('name')

const { data: pkg, error } = useFetchPackage(repository, architecture, name)

const canonical = computed(() => window.location.origin + router.resolve({ name: 'package', params: { repository: pkg.value.repository.name, architecture: pkg.value.repository.architecture, name: pkg.value.name } }).href)
</script>
