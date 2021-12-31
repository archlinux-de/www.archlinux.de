<template>
  <main class="container">
    <h1 class="mb-4">Impressum</h1>
    <table class="table table-sm table-borderless">
      <tbody>
        <tr>
          <th></th>
          <td>Pierre Schmitz</td>
        </tr>
        <tr>
          <th>Adresse</th>
          <td>Saarstr. 1<br/>
            53175 Bonn<br/>
            Germany
          </td>
        </tr>
        <tr>
          <th>Telefon</th>
          <td>+49 228 9716608</td>
        </tr>
        <tr>
          <th>E-Mail</th>
          <td><a href="mailto:pierre@archlinux.de">pierre@archlinux.de</a></td>
        </tr>
      </tbody>
    </table>
  </main>
</template>

<script setup>
import { useHead } from '@vueuse/head'

useHead({
  title: 'Impressum - archlinux.de',
  meta: [{ name: 'robots', content: 'noindex' }]
})
</script>
