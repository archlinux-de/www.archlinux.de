<template>
  <div class="card">
    <h3 class="card-title card-header">Aktuelle Pakete</h3>

    <div class="card-body p-1 p-lg-3">
      <table class="table table-sm table-borderless" :style="`min-height:${23 * limit}px`">
        <tr :key="key" v-for="(pkg, key) in data.items" data-test="recent-package">
          <td class="w-75" data-test="recent-package-name">
            <router-link class="p-0" :to="{
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
          <td :title="pkg.version" class="text-end text-truncate" data-test="recent-package-version">{{ pkg.version }}</td>
        </tr>
      </table>
    </div>

    <div class="card-footer text-muted d-inline-flex justify-content-between">
      <router-link to="packages" class="card-link">mehr Pakete</router-link>
      <a class="card-link" href="/packages/feed">Feed</a>
    </div>
  </div>
</template>

<script setup>
import { useFetchPackages } from '~/composables/useFetchPackages'

const props = defineProps({
  limit: {
    type: Number,
    required: false,
    default: 10
  }
})

const { data } = useFetchPackages({ limit: props.limit })
</script>
