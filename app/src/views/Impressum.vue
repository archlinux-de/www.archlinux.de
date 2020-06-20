<template>
  <b-container role="main" tag="main">
    <h1 class="mb-4">Impressum</h1>
    <b-table-simple small>
      <b-tbody>
        <b-tr>
          <b-th></b-th>
          <b-td>Pierre Schmitz</b-td>
        </b-tr>
        <b-tr>
          <b-th>Adresse</b-th>
          <b-td>Saarstr. 1<br/>
            53175 Bonn<br/>
            Germany
          </b-td>
        </b-tr>
        <b-tr>
          <b-th>Telefon</b-th>
          <b-td>+49 228 9716608</b-td>
        </b-tr>
        <b-tr>
          <b-th>E-Mail</b-th>
          <b-td><a href="mailto:pierre@archlinux.de">pierre@archlinux.de</a></b-td>
        </b-tr>
      </b-tbody>
    </b-table-simple>
  </b-container>
</template>

<script>
export default {
  name: 'Impressum',
  metaInfo: {
    title: 'Impressum',
    meta: [{ vmid: 'robots', name: 'robots', content: 'noindex' }]
  }
}
</script>
