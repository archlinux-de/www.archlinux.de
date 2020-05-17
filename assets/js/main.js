import Vue from 'vue'
import VueMeta from 'vue-meta'
import { BootstrapVue, BootstrapVueIcons } from 'bootstrap-vue'
import App from './App'
import router from './router'
import vueFilterPrettyBytes from 'vue-filter-pretty-bytes'
import createApiService from './services/ApiService'

Vue.config.productionTip = false
Vue.use(VueMeta)
Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)
Vue.use(vueFilterPrettyBytes)

new Vue({
  router,
  render: h => h(App),
  provide: {
    apiService: createApiService(fetch)
  }
}).$mount('#app')
