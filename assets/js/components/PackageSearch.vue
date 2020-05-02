<template>
  <form action="" method="get">
    <div class="input-group">
      <b-form-input debounce="250" id="searchfield" list="searchfield-list" v-model="term"></b-form-input>
      <b-form-datalist :options="options" id="searchfield-list"></b-form-datalist>
      <span class="input-group-btn"><b-button type="submit" variant="primary">Suchen</b-button></span>
    </div>
  </form>
</template>

<script>
export default {
  name: 'PackageSearch',
  inject: ['apiService'],
  data () {
    return {
      term: '',
      options: []
    }
  },
  methods: {
    fetchData () {
      if (this.term.length > 0) {
        this.apiService.fetchPackageSuggestions(this.term)
          .then(data => { this.options = data })
          .catch(() => {})
      } else {
        this.options = []
      }
    }
  },
  watch: {
    term () { this.fetchData() }
  }
}
</script>
