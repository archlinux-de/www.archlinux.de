<template>
  <div class="card" v-if="packages.length > 0">
    <h3 class="card-title card-header">Aktuelle Pakete</h3>

    <div class="card-body p-1 p-lg-3">
      <b-table-simple small fixed>
        <b-tr :key="key" v-for="(pkg, key) in packages" data-test="recent-package">
          <b-td class="pkgname w-75" data-test="recent-package-name">
            <router-link :to="{
              name: 'package',
               params:{
                repository: pkg.repository.name,
                architecture: pkg.repository.architecture,
                name:pkg.name
              }
            }">
              {{ pkg.name }}
            </router-link>
          </b-td>
          <b-td :title="pkg.version" class="text-right text-truncate" data-test="recent-package-version">{{ pkg.version }}</b-td>
        </b-tr>
      </b-table-simple>
    </div>

    <div class="card-footer text-muted d-inline-flex justify-content-between">
      <router-link to="packages" class="card-link">mehr Pakete</router-link>
      <a class="card-link" href="/packages/feed">Feed</a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RecentPackages',
  inject: ['apiService'],
  props: {
    limit: {
      type: Number,
      required: false,
      default: 10
    }
  },
  data () {
    return {
      packages: this.createInitialPackageList()
    }
  },
  methods: {
    fetchPackages () {
      this.apiService.fetchPackages({ limit: this.limit })
        .then(data => { this.packages = data.items })
        .catch(() => {})
    },
    createInitialPackageList () {
      return Array.from({ length: this.limit }, () => ({
        name: String.fromCharCode(8239),
        version: '',
        repository: { name: ' ', architecture: ' ' }
      }))
    }
  },
  mounted () {
    this.fetchPackages()
  }
}
</script>
