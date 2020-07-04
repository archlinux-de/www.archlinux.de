<template>
  <div id="page">
    <b-navbar class="navbar-border-brand nav-no-outline mb-4" toggleable="sm" type="dark" variant="dark">
      <b-navbar-brand :to="{name: 'start'}">
        <img alt="Arch Linux" height="40" :src="logo"/>
      </b-navbar-brand>

      <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

      <b-collapse id="nav-collapse" is-nav>
        <b-navbar-nav class="ml-auto mr-4">
          <b-nav-item :to="{name: 'start'}" class="d-none d-md-block ml-3 font-weight-bold" exact>Start</b-nav-item>
          <b-nav-item :to="{name: 'packages'}" class="ml-3 font-weight-bold">Pakete</b-nav-item>
          <b-nav-item href="https://bbs.archlinux.de/" class="ml-3 font-weight-bold">Forum</b-nav-item>
          <b-nav-item href="https://wiki.archlinux.de/" class="ml-3 font-weight-bold">Wiki</b-nav-item>
          <b-nav-item href="https://aur.archlinux.de/" class="d-none d-md-block ml-3 font-weight-bold">AUR</b-nav-item>
          <b-nav-item :to="{name: 'download'}" class="ml-3 font-weight-bold">Download</b-nav-item>
        </b-navbar-nav>
      </b-collapse>
    </b-navbar>

    <router-view id="content"/>

    <footer id="footer">
      <b-nav align="right" class="nav-no-outline">
        <b-nav-item :to="{name: 'privacy-policy'}">Datenschutz</b-nav-item>
        <b-nav-item :to="{name: 'impressum'}">Impressum</b-nav-item>
      </b-nav>
    </footer>
  </div>
</template>

<style lang="scss">
  @import "@/assets/css/archlinux-bootstrap";

  .navbar-border-brand {
    border-bottom: 0.313rem solid $primary;
  }

  .nav-no-outline {
    a:focus,
    button:focus {
      outline: 0;
    }
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

  @import "~bootstrap/scss/bootstrap.scss";
  @import "~bootstrap-vue/src/index.scss";
</style>

<script>
import { BCollapse, BNav, BNavbar, BNavbarBrand, BNavbarNav, BNavbarToggle, BNavItem } from 'bootstrap-vue'
import LogoImage from '@/assets/images/archlogo.svg'
import IconImage from '@/assets/images/archicon.svg'

export default {
  name: 'App',
  components: {
    BNavbar,
    BNavbarBrand,
    BNavbarToggle,
    BCollapse,
    BNavbarNav,
    BNavItem,
    BNav
  },
  metaInfo () {
    return {
      title: 'archlinux.de',
      titleTemplate: '%s - archlinux.de',
      meta: [{ vmid: 'robots', name: 'robots', content: 'index,follow' }],
      link: [{ rel: 'icon', href: this.icon, sizes: 'any', type: 'image/svg+xml' }]
    }
  },
  data () {
    return {
      logo: LogoImage,
      icon: IconImage
    }
  }
}
</script>
