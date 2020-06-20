<template>
  <b-col cols="12" md="6" lg="4" v-if="relations.length > 0">
    <h3>{{ title }}</h3>
    <ul class="list-unstyled pl-4">
      <li :key="key" v-for="(relation, key) in relations">
        <router-link v-if="relation.target"
                     :to="{name: 'package', params: {
                        repository: relation.target.repository.name,
                        architecture: relation.target.repository.architecture,
                        name: relation.target.name}}"
        >{{ relation.name }}<!--
        --></router-link><!--
        --><span v-else>{{ relation.name }}</span>{{ relation.version }}
      </li>
    </ul>
  </b-col>
</template>

<script>
export default {
  name: 'PackageRelations',
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
    fetchRelations () {
      this.apiService.fetchPackageDependencies(this.repository, this.architecture, this.name, this.type)
        .then(data => { this.relations = data })
        .catch(() => {})
    }
  },
  mounted () {
    this.fetchRelations()
  },
  watch: {
    $props: {
      handler () {
        this.fetchRelations()
      },
      deep: true
    }
  }
}
</script>
