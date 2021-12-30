<template>
  <div class="col-12 col-md-6 col-lg-4" v-if="relations.length > 0">
    <h3>{{ title }}</h3>
    <ul class="list-unstyled ps-4 link-list">
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
  </div>
</template>

<script>
export default {
  inject: ['apiService'],
  props: {
    repository: {
      type: String,
      required: true
    },
    architecture: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true
    },
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
  methods: {
    fetchInverseRelations () {
      this.apiService.fetchPackageInverseDependencies(this.repository, this.architecture, this.name, this.type)
        .then(data => { this.relations = data })
        .catch(() => {})
    }
  },
  mounted () {
    this.fetchInverseRelations()
  },
  watch: {
    $props: {
      handler () {
        this.fetchInverseRelations()
      },
      deep: true
    }
  }
}
</script>
