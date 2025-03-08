<template>
  <div class="news-items">
    <loading-spinner absolute v-if="newsItems.items.length === 0"></loading-spinner>
    <div :key="newsItem.id" class="mb-5" v-for="newsItem in newsItems.items" data-test="news-item">
      <div
        class="d-lg-flex justify-content-between align-items-baseline border border-top-0 border-start-0 border-end-0 mb-2">
        <h2 class="text-break">
          <router-link :to="{name: 'news-item', params: {id: newsItem.id, slug: newsItem.slug}}">
            {{ newsItem.title }}
          </router-link>
        </h2>
        <div data-test="news-item-last-modified">{{ new Date(newsItem.lastModified).toLocaleDateString('de-DE') }}</div>
      </div>
      <div class="text-break" v-html="newsItem.description" data-test="news-item-description"></div>
    </div>

    <div v-if="newsItems.items.length > 0" class="py-2 mb-5">
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

<script setup>
import LoadingSpinner from '~/components/LoadingSpinner'
import { useFetchNewsItems } from '~/composables/useFetchNewsItems'

const props = defineProps({
  limit: {
    type: Number,
    required: false,
    default: 10
  }
})

const { data: newsItems } = useFetchNewsItems({ limit: props.limit })
</script>
