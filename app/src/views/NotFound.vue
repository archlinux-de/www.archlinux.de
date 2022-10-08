<template>
  <main class="container">
    <Head>
      <title>Datei nicht gefunden</title>
      <meta name="robots" content="noindex">
    </Head>
    <div class="alert alert-danger" role="alert">
      <h4 class="alert-heading">Datei nicht gefunden</h4>
      <p>Die angefragte Datei <strong>{{ currentUrl }}</strong> wurde nicht gefunden.</p>
      <hr>
      <p class="mb-0">Sollte das Problem weiterhin bestehen, melde Dich bei uns. Wir werden es so
        schnell wie möglich beheben.</p>
    </div>
  </main>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@vueuse/head'

const currentUrl = computed(() => window.location.pathname)
</script>
