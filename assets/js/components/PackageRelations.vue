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
  props: {
    relations: {
      type: Array,
      required: true
    },
    title: {
      type: String,
      required: true
    }
  }
}
</script>
