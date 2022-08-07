<template>
  <form :action="$router.resolve({name: 'packages'}).href" method="get">
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
import { ref } from 'vue'
import { useFetchPackageSuggestions } from '~/composables/useFetchPackageSuggestions'

const term = ref('')
const { data: options } = useFetchPackageSuggestions(term)
</script>
