<template>
  <form :action="this.$router.resolve({name: 'packages'}).href" method="get">
    <div class="input-group">
      <input
        class="form-control"
        type="text"
        name="search"
        id="searchfield"
        list="searchfield-list"
        v-model="term"
        autocomplete="off">
      <datalist id="searchfield-list">
        <option :key="key" :value="option" v-for="(option, key) in options"></option>
      </datalist>
      <span class="input-group-btn"><button class="btn btn-primary" type="submit">Suchen</button></span>
    </div>
  </form>
</template>

<script>
export default {
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
