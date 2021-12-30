<template>
  <span :title="popularityLabel" class="package-popularity">
      <span v-html="starFill" :key="'fill' + id" class="text-primary" v-for="id in fullStars"></span><!--
    --><span v-html="starHalf" :key="'half' + id" class="text-primary" v-for="id in halfStars"></span><!--
    --><span v-html="star" :key="'empty' + id" class="text-primary" v-for="id in emptyStars"></span>
  </span>
</template>

<style lang="scss">
.package-popularity {
  .bi {
    vertical-align: unset;
    margin-top: 0.25em;
  }
}
</style>

<script>
/* eslint-disable import/no-webpack-loader-syntax */
import starFill from '!!svg-inline-loader!bootstrap-icons/icons/star-fill.svg'
import starHalf from '!!svg-inline-loader!bootstrap-icons/icons/star-half.svg'
import star from '!!svg-inline-loader!bootstrap-icons/icons/star.svg'
/* eslint-enable */

export default {
  inject: ['apiService'],
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
  data () {
    return {
      starFill,
      starHalf,
      star
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
