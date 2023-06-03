<template>
  <div id="page">
    <Head>
      <title></title>
      <meta name="robots" content="index,follow">
      <meta name="theme-color" content="#333">
      <link rel="icon" :href="IconImage" sizes="any" type="image/svg+xml">
      <link rel="manifest" href="/manifest.webmanifest">
      <link rel="search" type="application/opensearchdescription+xml" href="/packages/opensearch">
    </Head>
    <nav class="navbar navbar-expand-md navbar-dark navbar-border-brand bg-dark nav-no-outline mb-4">
      <div class="container-fluid">
        <router-link :to="{name: 'start'}" class="navbar-brand">
          <img alt="Arch Linux" height="40" width="190" :src="LogoImage" class="d-inline-block align-text-top"/>
        </router-link>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#archlinux-navbar"
                aria-controls="archlinux-navbar" aria-expanded="false" aria-label="Navigation umschalten">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="archlinux-navbar">
          <ul class="navbar-nav ms-auto mb-2 mb-md-0">
            <li class="nav-item">
              <router-link :to="{name: 'start'}" exact class="nav-link ms-3 fw-bold">Start</router-link>
            </li>
            <li class="nav-item">
              <router-link :to="{name: 'packages'}" class="nav-link ms-3 fw-bold">Pakete</router-link>
            </li>
            <li class="nav-item">
              <a href="https://forum.archlinux.de/" class="nav-link ms-3 fw-bold">Forum</a>
            </li>
            <li class="nav-item">
              <a href="https://wiki.archlinux.de/" class="nav-link ms-3 fw-bold">Wiki</a>
            </li>
            <li class="nav-item">
              <a href="https://aur.archlinux.org/" class="nav-link ms-3 fw-bold" rel="noopener">AUR</a>
            </li>
            <li class="nav-item">
              <router-link :to="{name: 'download'}" class="nav-link ms-3 fw-bold">Download</router-link>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <router-view id="content"/>

    <footer id="footer">
      <nav class="nav nav-no-outline justify-content-end mt-4">
        <router-link class="nav-link" :to="{name: 'privacy-policy'}">Datenschutz</router-link>
        <router-link class="nav-link" :to="{name: 'impressum'}">Impressum</router-link>
      </nav>
    </footer>
  </div>
</template>

<style lang="scss">
@import "./assets/css/archlinux-bootstrap";
@import "./assets/css/import-bootstrap";

.navbar-border-brand {
  border-bottom: 0.313rem solid $primary;
}

.nav-no-outline a:focus {
  outline: 0;
}

#page {
  position: relative;
  min-height: 100vh;
}

#content {
  padding-bottom: 2.3rem;
}

#footer {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 2.3rem;
}

// increase tap size for mobile
@include media-breakpoint-down(md) {
  .link-list li {
    margin-bottom: $spacer;
  }
}

.table-fixed {
  table-layout: fixed;
}

.bi {
  width: 1em;
  height: 1em;
}

pre:has(> code) {
  background-color: var(--bs-secondary-bg);
  color: var(--bs-secondary-color);
  border-width: $border-width;
  border-style: $border-style;
  border-color: var(--bs-border-color);
  // stylelint-disable-next-line
  padding: map-get($gutters, 2);
}
</style>

<script setup>
import Collapse from 'bootstrap/js/src/collapse'
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useHead, Head } from '@vueuse/head'
import LogoImage from '~/assets/images/archlogo.svg'
import IconImage from '~/assets/images/archicon.svg'

useRouter().beforeEach(() => {
  const navbar = Collapse.getInstance('#archlinux-navbar')
  if (navbar) {
    navbar.hide()
  }
})

useHead({ titleTemplate: '%s - archlinux.de' })

onMounted(() => {
  if (process.env.NODE_ENV === 'production' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js')
    })
  }
})
</script>
