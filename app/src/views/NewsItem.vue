<template>
  <main class="container">

    <div class="alert alert-danger" v-show="error != ''">{{ error }}</div>

    <template v-if="news.id">
      <Head>
        <title>{{ news.title }} - archlinux.de</title>
        <link rel="canonical" :href="createCanonical()">
        <meta name="description" :content="createDescription()">
      </Head>
      <h1 class="mb-4">{{ news.title }}</h1>
      <div class="mb-3 text-muted">
        {{ (new Date(news.lastModified)).toLocaleDateString('de-DE') }}
        &ndash;
        <a v-if="news.author.uri" class="text-muted" :href="news.author.uri">{{ news.author.name }}</a>
        <span v-else>{{ news.author.name }}</span>
      </div>
      <div class="text-break mb-4" v-html="news.description"></div>
      <router-link :to="{name: 'news'}" class="btn btn-outline-secondary btn-sm">zum Archiv</router-link>
      <a class="btn btn-primary btn-sm" :href="news.link">Kommentare</a>

      <component :is="'script'" type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "NewsArticle",
          "headline": "{{ JSON.stringify(news.title) }}",
          "datePublished": "{{ (new Date(news.lastModified)).toJSON() }}",
          "author": [
            {
              "@type": "Person",
              "name": "{{ news.author.name }}",
              "url": "{{ news.author.uri }}"
            }
          ],
          "discussionUrl": "{{ news.link }}"
        }
      </component>
    </template>
    <template v-else>
      <Head>
        <meta name="robots" content="noindex,follow">
      </Head>
    </template>
  </main>
</template>

<script>
import { Head } from '@vueuse/head'

export default {
  components: {
    Head
  },
  inject: ['apiService'],
  data () {
    return {
      news: {},
      error: ''
    }
  },
  methods: {
    fetchNewsItem () {
      this.apiService.fetchNewsItem(this.$route.params.id)
        .then(data => {
          this.news = data
        })
        .catch(error => {
          this.error = error
        })
    },
    createDescription () {
      return new DOMParser()
        .parseFromString(this.news.description, 'text/html')
        .body
        .textContent
        .substr(0, 100)
    },
    createCanonical () {
      return window.location.origin + this.$router.resolve({
        name: 'news-item',
        params: { id: this.news.id, slug: this.news.slug }
      }).href
    }
  },
  mounted () {
    this.fetchNewsItem()
  }
}
</script>
