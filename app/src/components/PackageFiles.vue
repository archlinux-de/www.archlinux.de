<template>
  <div>
    <button class="btn btn-outline-primary btn-sm ms-4"
              v-if="files.length === 0" v-on:click.once="fetchFiles">
      Dateien anzeigen
    </button>
    <ul class="list-unstyled ms-4 overflow-auto">
      <li :key="key" v-for="(file, key) in files" :class="file.match(/\/$/) ? 'text-muted' : ''">{{ file }}</li>
    </ul>
  </div>
</template>

<script setup>
import { inject, ref, watch } from 'vue'

const props = defineProps({
  repository: {
    type: String,
    required: true
  },
  architecture: {
    type: String,
    required: true
  },
  name: {
    type: String,
    required: true
  }
})

const apiService = inject('apiService')

const files = ref([])

const fetchFiles = () => {
  apiService.fetchPackageFiles(
    props.repository,
    props.architecture,
    props.name)
    .then(data => {
      files.value = data.length ? data : ['Das Paket enthält keine Dateien']
    })
    .catch(() => {})
}

watch(props, () => { files.value = [] }, { deep: true })
</script>
