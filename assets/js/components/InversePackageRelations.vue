<template>
  <b-col cols="12" md="6" lg="4" v-if="relations.length > 0">
    <h3>{{ title }}</h3>
    <ul class="list-unstyled pl-4">
      <li :key="key" v-for="(relation, key) in relations">
        <router-link
          :to="{name: 'package', params: {
              repository: relation.repository.name,
              architecture: relation.repository.architecture,
              name: relation.name}}">
          {{ relation.name }}
        </router-link>
      </li>
    </ul>
  </b-col>
</template>

<script>
export default {
  name: 'InversePackageRelations',
  inject: ['apiService'],
  props: {
    type: {
      type: String,
      required: true
    },
    title: {
      type: String,
      required: true
    }
  },
  data () {
    return {
      relations: []
    }
  },
  watch: {
    $route: 'fetchRelations'
  },
  methods: {
    fetchRelations () {
      this.apiService.fetchPackageInverseDependencies(
        this.$route.params.repository,
        this.$route.params.architecture,
        this.$route.params.name, this.type
      )
        .then(data => { this.relations = data })
        .catch(() => {})
    }
  },
  mounted () {
    this.fetchRelations()
  }
}
</script>
