<template>
  <div class="col-12 col-md-6 col-lg-4" v-if="relations.length > 0">
    <h3>{{ title }}</h3>
    <ul class="list-unstyled ps-4 link-list" :data-test="`package-relations-${type}`">
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
  </div>
</template>

<script setup>
import { useFetchPackageDependencies } from '~/composables/useFetchPackageDependencies'

const props = defineProps({
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
})

const { data: relations } = useFetchPackageDependencies(props.repository, props.architecture, props.name, props.type)
</script>
