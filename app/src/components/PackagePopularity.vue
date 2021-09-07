<template>
  <span :title="popularityLabel">
      <b-icon-star-fill :key="'fill' + id" variant="primary" v-for="id in fullStars"></b-icon-star-fill><!--
    --><b-icon-star-half :key="'half' + id" variant="primary" v-for="id in halfStars"></b-icon-star-half><!--
    --><b-icon-star :key="'empty' + id" variant="primary" v-for="id in emptyStars">></b-icon-star>
  </span>
</template>

<script>
import { BIconStar, BIconStarFill, BIconStarHalf } from 'bootstrap-vue'

export default {
  name: 'PackagePopularity',
  inject: ['apiService'],
  components: {
    BIconStar,
    BIconStarFill,
    BIconStarHalf
  },
  props: {
    popularity: {
      type: Object,
      required: true,
      default: null,
      validator: value => value.popularity >= 0 && value.popularity <= 100
    },
    stars: {
      type: Number,
      required: false,
      default: 5,
      validator: value => value > 0
    }
  },
  computed: {
    popularityLabel: function () {
      return (new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 })).format(this.popularity.popularity) +
        '%' + (this.popularity.count ? `, ${this.popularity.count} von ${this.popularity.samples}` : '')
    },
    fullStars: function () {
      if (this.popularity.popularity <= 0 || this.stars < 1) {
        return 0
      }
      if (this.popularity.popularity > 100) {
        return this.stars
      }
      return Math.floor(this.popularity.popularity / 100 * this.stars)
    },
    halfStars: function () {
      if (this.popularity.popularity > 100 || this.popularity.popularity <= 0 || this.stars < 1) {
        return 0
      }
      return (Math.ceil(this.popularity.popularity / 100 * this.stars) - Math.floor(this.popularity.popularity / 100 * this.stars)) >= 0.5 ? 1 : 0
    },
    emptyStars: function () {
      return this.stars - this.fullStars - this.halfStars
    }
  }
}
</script>
