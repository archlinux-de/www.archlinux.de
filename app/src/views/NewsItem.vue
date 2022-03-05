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
import { inject, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Head } from '@vueuse/head'

const apiService = inject('apiService')

const news = ref({})
const error = ref('')

const fetchNewsItem = () => {
  apiService.fetchNewsItem(useRoute().params.id)
    .then(data => {
      news.value = data
    })
    .catch(err => {
      error.value = err
    })
}

const createDescription = () => new DOMParser()
  .parseFromString(news.value.description, 'text/html')
  .body
  .textContent
  .substr(0, 100)

const createCanonical = () => window.location.origin + useRouter().resolve({
  name: 'news-item',
  params: { id: news.value.id, slug: news.value.slug }
}).href

onMounted(() => { fetchNewsItem() })
</script>
