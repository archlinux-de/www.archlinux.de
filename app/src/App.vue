<template>
  <div id="page">
    <nav class="navbar navbar-expand-md navbar-dark navbar-border-brand bg-dark nav-no-outline mb-4">
      <div class="container-fluid">
        <router-link :to="{name: 'start'}" class="navbar-brand">
          <img alt="Arch Linux" height="40" width="190" :src="logo" class="d-inline-block align-text-top"/>
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
</style>

<script>
import 'bootstrap/js/src/collapse'
import LogoImage from './assets/images/archlogo.svg'
import IconImage from './assets/images/archicon.svg'

export default {
  metaInfo () {
    return {
      title: 'archlinux.de',
      titleTemplate: '%s - archlinux.de',
      meta: [
        { vmid: 'robots', name: 'robots', content: 'index,follow' },
        { name: 'theme-color', content: '#333' }
      ],
      link: [
        { rel: 'icon', href: this.icon, sizes: 'any', type: 'image/svg+xml' },
        { rel: 'manifest', href: '/manifest.webmanifest' },
        { rel: 'search', type: 'application/opensearchdescription+xml', href: '/packages/opensearch' }
      ]
    }
  },
  data () {
    return {
      logo: LogoImage,
      icon: IconImage
    }
  },
  mounted () {
    if (process.env.NODE_ENV === 'production' && 'serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
      })
    }
  }
}
</script>
