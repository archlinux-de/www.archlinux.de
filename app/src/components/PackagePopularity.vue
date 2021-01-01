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
      type: Number,
      required: true,
      default: 0,
      validator: value => value >= 0 && value <= 100
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
      return (new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 })).format(this.popularity) + '%'
    },
    fullStars: function () {
      if (this.popularity <= 0 || this.stars < 1) {
        return 0
      }
      if (this.popularity > 100) {
        return this.stars
      }
      return Math.floor(this.popularity / 100 * this.stars)
    },
    halfStars: function () {
      if (this.popularity > 100 || this.popularity <= 0 || this.stars < 1) {
        return 0
      }
      return (Math.ceil(this.popularity / 100 * this.stars) - Math.floor(this.popularity / 100 * this.stars)) >= 0.5 ? 1 : 0
    },
    emptyStars: function () {
      return this.stars - this.fullStars - this.halfStars
    }
  }
}
</script>
