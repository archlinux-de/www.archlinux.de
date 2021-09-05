<template>
  <b-container role="main" tag="main">
    <b-row>
      <b-col cols="12" xl="8">
        <b-jumbotron class="p-2 p-xl-3">
          <h1>Willkommen bei Arch Linux</h1>
          <p>
            <strong>Arch Linux</strong> ist eine <em>flexible</em> und <em>leichtgewichtige</em>
            Distribution
            für jeden
            erdenklichen Einsatz-Zweck. Ein einfaches Grundsystem kann nach den Bedürfnissen des jeweiligen
            Nutzers nahezu
            beliebig erweitert werden.
          </p>
          <p>
            Nach einem gleitenden Release-System bieten wir zur Zeit kompilierte Pakete für die
            <em>x86_64</em>-Architektur an. Zusätzliche Werkzeuge ermöglichen zudem den schnellen Eigenbau
            von
            Paketen.
          </p>
          <p>
            Arch Linux ist daher eine perfekte Distribution für erfahrene Anwender &mdash; und solche, die
            es
            werden wollen...
          </p>
          <div class="text-right">
            <b-button href="https://wiki.archlinux.de/title/%C3%9Cber_Arch_Linux" size="sm" variant="outline-primary">
              mehr über Arch Linux
            </b-button>
          </div>
        </b-jumbotron>
        <news-item-list :limit="6"></news-item-list>
      </b-col>

      <b-col cols="12" xl="4">

        <div class="card mb-4">
          <div class="card-body">
            <package-search></package-search>
          </div>
        </div>

        <recent-packages :limit="20"></recent-packages>

        <h4 class="mt-5">Dokumentation</h4>
        <ul class="list-unstyled pl-4 link-list">
          <li><a href="https://wiki.archlinux.de/">Wiki</a></li>
          <li><a href="https://wiki.archlinux.de/title/Arch_Install_Scripts">Arch Install Scripts</a></li>
          <li><a href="https://wiki.archlinux.de/title/Anleitung_f%C3%BCr_Einsteiger">
            Anleitung für Einsteiger
          </a></li>
        </ul>
        <h4 class="mt-4">Gemeinschaft</h4>
        <ul class="list-unstyled pl-4 link-list">
          <li><a href="https://planet.archlinux.de/">Planet archlinux.de</a></li>
          <li><a href="https://www.archlinux.org/" rel="noopener">Archlinux.org</a></li>
          <li><a href="https://wiki.archlinux.org/index.php/International_Communities" rel="noopener">
            Internationale Gemeinschaft
          </a></li>
        </ul>
        <h4 class="mt-4">Unterstützung</h4>
        <ul class="list-unstyled pl-4 link-list">
          <li><a href="https://www.archlinux.org/donate/" rel="noopener">Spenden (international)</a></li>
        </ul>
        <h4 class="mt-4">Entwicklung</h4>
        <ul class="list-unstyled pl-4 link-list">
          <li>
            <router-link to="packages">Pakete</router-link>
          </li>
          <li><a href="https://www.archlinux.org/packages/differences/" rel="noopener">Architektur-Unterschiede</a></li>
          <li><a href="https://aur.archlinux.de/">AUR</a></li>
          <li><a href="https://bugs.archlinux.org/" rel="noopener">Bug Tracker</a></li>
          <li><a href="https://www.archlinux.org/svn/" rel="noopener">SVN Repositories</a></li>
          <li><a href="https://projects.archlinux.org/" rel="noopener">Projekte in Git</a></li>
          <li><a href="https://github.com/archlinux-de" rel="nofollow noopener">archlinux.de Projekte in Git</a></li>
          <li><a href="https://wiki.archlinux.org/index.php/DeveloperWiki" rel="noopener">Entwickler-Wiki</a></li>
        </ul>
        <h4 class="mt-4">Informationen</h4>
        <ul class="list-unstyled pl-4 link-list">
          <li><a href="https://wiki.archlinux.de/title/%C3%9Cber_Arch_Linux">über Arch Linux</a></li>
          <li>
            <router-link to="download">Arch herunterladen</router-link>
          </li>
          <li>
            <router-link to="releases">Release-Archiv</router-link>
          </li>
          <li><a href="https://wiki.archlinux.de/title/Arch_in_den_Medien">Arch in den Medien</a></li>
          <li><a href="https://www.archlinux.org/art/" rel="noopener">Logos</a></li>
          <li><a href="https://www.archlinux.org/people/developers/" rel="noopener">Entwickler</a></li>
          <li><a href="https://www.archlinux.org/people/trusted-users/" rel="noopener">Trusted Users</a></li>
          <li><a href="https://www.archlinux.org/people/developer-fellows/" rel="noopener">Ehemalige Entwickler</a></li>
          <li><a href="https://www.archlinux.org/people/trusted-user-fellows/" rel="noopener">Ehemalige Trusted
            Users</a>
          </li>
          <li>
            <router-link to="mirrors">Mirror-Status</router-link>
          </li>
        </ul>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
import PackageSearch from '../components/PackageSearch'
import NewsItemList from '../components/NewsItemList'
import RecentPackages from '../components/RecentPackages'

export default {
  name: 'Start',
  components: {
    PackageSearch,
    NewsItemList,
    RecentPackages
  },
  metaInfo () {
    return {
      titleTemplate: null,
      link: [{ rel: 'canonical', href: window.location.origin + this.$router.resolve({ name: 'start' }).href }]
    }
  }
}
</script>
