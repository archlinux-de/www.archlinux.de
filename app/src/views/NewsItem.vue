<template>
  <b-container role="main" tag="main">

    <b-alert :show="error != ''" variant="danger">{{ error }}</b-alert>

    <template v-if="news.id">
      <h1 class="mb-4">{{ news.title }}</h1>
      <div class="mb-3 text-muted">
        {{ (new Date(news.lastModified)).toLocaleDateString('de-DE') }}
        &ndash;
        <a v-if="news.author.uri" class="text-muted" :href="news.author.uri">{{ news.author.name }}</a>
        <span v-else>{{ news.author.name }}</span>
      </div>
      <div class="text-break mb-4" v-html="news.description"></div>
      <router-link :to="{name: 'news'}" class="btn btn-outline-secondary btn-sm" role="button">zum Archiv</router-link>
      <a class="btn btn-primary btn-sm" role="button" :href="news.link">Kommentare</a>
    </template>
  </b-container>
</template>

<script>
export default {
  name: 'NewsItem',
  metaInfo () {
    if (this.news.id) {
      return {
        title: this.news.title,
        link: [{
          rel: 'canonical',
          href: window.location.origin + this.$router.resolve({
            name: 'news-item',
            params: { id: this.news.id, slug: this.news.slug }
          }).href
        }],
        meta: [{ name: 'description', content: this.createDescription() }]
      }
    } else {
      return {
        meta: [{ vmid: 'robots', name: 'robots', content: 'noindex,follow' }]
      }
    }
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
        .then(data => { this.news = data })
        .catch(error => { this.error = error })
    },
    createDescription () {
      return new DOMParser()
        .parseFromString(this.news.description, 'text/html')
        .body
        .textContent
        .substr(0, 100)
    }
  },
  mounted () {
    this.fetchNewsItem()
  }
}
</script>
