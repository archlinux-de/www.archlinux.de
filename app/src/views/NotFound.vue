<template>
  <b-container role="main" tag="main">
    <b-alert show variant="danger">
      <h4 class="alert-heading">Datei nicht gefunden</h4>
      <p>Die angefragte Datei <strong>{{ currentUrl }}</strong> wurde nicht gefunden.</p>
      <hr>
      <p class="mb-0">Sollte das Problem weiterhin bestehen, melde Dich bei uns. Wir werden es so
        schnell wie möglich beheben.</p>
    </b-alert>
  </b-container>
</template>

<script>
export default {
  name: 'NotFound',
  data () {
    return { currentUrl: window.location.pathname }
  },
  metaInfo () {
    return {
      title: 'Datei nicht gefunden',
      meta: [{ vmid: 'robots', name: 'robots', content: 'noindex,nofollow' }]
    }
  }
}
</script>
