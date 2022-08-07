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

<script setup>
import { computed } from 'vue'
/* eslint-disable import/no-webpack-loader-syntax */
import starFill from '!!svg-inline-loader!bootstrap-icons/icons/star-fill.svg'
import starHalf from '!!svg-inline-loader!bootstrap-icons/icons/star-half.svg'
import star from '!!svg-inline-loader!bootstrap-icons/icons/star.svg'
/* eslint-enable */

const props = defineProps({
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
})

const popularityLabel = computed(() =>
  (new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 }))
    .format(props.popularity.popularity) + '%' +
  (props.popularity.count ? `, ${props.popularity.count} von ${props.popularity.samples}` : '')
)

const fullStars = computed(() => {
  if (props.popularity.popularity <= 0 || props.stars < 1) {
    return 0
  }
  if (props.popularity.popularity > 100) {
    return props.stars
  }
  return Math.floor(props.popularity.popularity / 100 * props.stars)
})

const halfStars = computed(() => {
  if (props.popularity.popularity > 100 || props.popularity.popularity <= 0 || props.stars < 1) {
    return 0
  }
  return (Math.ceil(props.popularity.popularity / 100 * props.stars) - Math.floor(props.popularity.popularity / 100 * props.stars)) >= 0.5 ? 1 : 0
})

const emptyStars = computed(() => props.stars - fullStars.value - halfStars.value)
</script>
