<template>
  <div class="news-items">
    <loading-spinner absolute v-if="newsItems.length === 0"></loading-spinner>
    <div :key="newsItem.id" class="mb-5" v-for="newsItem in newsItems">
      <div
        class="d-lg-flex justify-content-between align-items-baseline border border-top-0 border-left-0 border-right-0 mb-2">
        <h2 class="text-break">
          <router-link :to="{name: 'news-item', params: {id: newsItem.id, slug: newsItem.slug}}">
            {{ newsItem.title }}
          </router-link>
        </h2>
        <div>{{ new Date(newsItem.lastModified).toLocaleDateString('de-DE') }}</div>
      </div>
      <div class="text-break" v-html="newsItem.description"></div>
    </div>

    <div v-if="newsItems.length > 0" class="py-2 mb-5">
      <a class="btn btn-outline-secondary btn-sm" href="/news/feed">Feed</a>
      <router-link :to="{name: 'news'}" class="btn btn-primary btn-sm">zum Archiv</router-link>
    </div>
  </div>
</template>

<style scoped>
  .news-items {
    min-height: 100vh;
  }
</style>

<script>
import LoadingSpinner from './LoadingSpinner'

export default {
  name: 'NewsItemList',
  inject: ['apiService'],
  components: {
    LoadingSpinner
  },
  props: {
    limit: {
      type: Number,
      required: false,
      default: 10
    }
  },
  data () {
    return {
      newsItems: []
    }
  },
  methods: {
    fetchNewsItems () {
      this.apiService.fetchNewsItems({ limit: this.limit })
        .then(data => { this.newsItems = data.items })
        .catch(() => {})
    }
  },
  mounted () {
    this.fetchNewsItems()
  }
}
</script>
