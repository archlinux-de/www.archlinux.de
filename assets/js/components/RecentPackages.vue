<template>
  <div class="card" v-if="packages.length > 0">
    <h3 class="card-title card-header">Aktuelle Pakete</h3>

    <div class="card-body p-1 p-lg-3">
      <table class="table table-sm table-fixed">
        <tr :key="key" v-for="(pkg, key) in packages">
          <td class="pkgname w-75">
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
          </td>
          <td :title="pkg.version" class="text-right text-truncate">{{ pkg.version }}</td>
        </tr>
      </table>
    </div>

    <div class="card-footer text-muted d-inline-flex justify-content-between">
      <router-link to="packages" class="card-link">mehr Pakete</router-link>
      <a class="card-link" href="">Feed</a>
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
      packages: []
    }
  },
  methods: {
    fetchPackages () {
      this.apiService.fetchPackages({ limit: this.limit })
        .then(data => { this.packages = data.items })
        .catch(() => {})
    }
  },
  mounted () {
    this.fetchPackages()
  }
}
</script>
