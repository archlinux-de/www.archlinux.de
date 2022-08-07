<template>
  <main class="container">

    <div class="alert alert-danger" v-if="error">{{ error }}</div>

    <template v-if="news.id">
      <Head>
        <title>{{ news.title }} - archlinux.de</title>
        <link rel="canonical" :href="canonical">
        <meta name="description" :content="description">
      </Head>
      <h1 class="mb-4">{{ news.title }}</h1>
      <div class="mb-3 text-muted">
        <span data-test="news-date">{{ (new Date(news.lastModified)).toLocaleDateString('de-DE') }}</span>
        &ndash;
        <a v-if="news.author.uri" class="text-muted" :href="news.author.uri" data-test="news-author">{{ news.author.name }}</a>
        <span v-else data-test="news-author">{{ news.author.name }}</span>
      </div>
      <div class="text-break mb-4" v-html="news.description" data-test="news-content"></div>
      <router-link :to="{name: 'news'}" class="btn btn-outline-secondary btn-sm">zum Archiv</router-link>
      <a class="btn btn-primary btn-sm" :href="news.link" data-test="news-comments-link">Kommentare</a>

      <component :is="'script'" type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "NewsArticle",
          "headline": {{ JSON.stringify(news.title) }},
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

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Head } from '@vueuse/head'
import { useRouteParams } from '@vueuse/router'
import { useFetchNewsItem } from '~/composables/useFetchNewsItem'

const router = useRouter()
const id = useRouteParams('id')

const { data: news, error } = useFetchNewsItem(id)

const description = computed(() => new DOMParser()
  .parseFromString(news.value.description, 'text/html')
  .body
  .textContent
  .substr(0, 100))

const canonical = computed(() => window.location.origin + router.resolve({ name: 'news-item', params: { id: news.value.id, slug: news.value.slug } }).href)
</script>
