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

<script setup>
import { inject, ref, watch } from 'vue'

const apiService = inject('apiService')

const term = ref('')
const options = ref([])

const fetchData = () => {
  if (term.value.length > 0) {
    apiService.fetchPackageSuggestions(term.value)
      .then(data => { options.value = data })
      .catch(() => {})
  } else {
    options.value = []
  }
}

watch(term, () => fetchData())
</script>
