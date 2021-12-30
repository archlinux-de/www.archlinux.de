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

<script>
export default {
  inject: ['apiService'],
  props: {
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
  },
  data () {
    return {
      files: []
    }
  },
  methods: {
    fetchFiles () {
      this.apiService.fetchPackageFiles(
        this.repository,
        this.architecture,
        this.name)
        .then(data => {
          this.files = data.length ? data : ['Das Paket enthält keine Dateien']
        })
        .catch(() => {})
    }
  },
  watch: {
    $props: {
      handler () {
        this.files = []
      },
      deep: true
    }
  }
}
</script>
