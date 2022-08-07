<template>
  <div>
    <button class="btn btn-outline-primary btn-sm ms-4"
              v-if="files.length === 0" v-on:click.once="fetchFiles" data-test="package-show-files">
      Dateien anzeigen
    </button>
    <ul class="list-unstyled ms-4 overflow-auto" data-test="package-files">
      <li :key="key" v-for="(file, key) in files" :class="file.match(/\/$/) ? 'text-muted' : ''">{{ file }}</li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useFetchPackageFiles } from '~/composables/useFetchPackageFiles'

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

const files = ref([])

const fetchFiles = async () => {
  const { data } = await useFetchPackageFiles(props.repository, props.architecture, props.name)
  files.value = data.value
}
</script>
