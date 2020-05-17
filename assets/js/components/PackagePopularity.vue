<template>
  <span :title="popularityLabeL">
      <b-icon-star-fill :key="'fill' + id" variant="primary" v-for="id in fullStars"></b-icon-star-fill><!--
    --><b-icon-star-half :key="'half' + id" variant="primary" v-for="id in halfStars"></b-icon-star-half><!--
    --><b-icon-star :key="'empty' + id" variant="primary" v-for="id in emptyStars">></b-icon-star>
  </span>
</template>

<script>
export default {
  name: 'PackagePopularity',
  inject: ['apiService'],
  props: {
    popularity: {
      type: Number,
      required: true
    },
    stars: {
      type: Number,
      required: false,
      default: 5
    }
  },
  computed: {
    popularityLabeL: function () {
      return (new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 })).format(this.popularity) + '%'
    },
    fullStars: function () {
      return Math.floor(this.popularity / 100 * this.stars)
    },
    halfStars: function () {
      return (Math.ceil(this.popularity / 100 * this.stars) - Math.floor(this.popularity / 100 * this.stars)) >= 0.5 ? 1 : 0
    },
    emptyStars: function () {
      return this.stars - this.fullStars - this.halfStars
    }
  }
}
</script>
