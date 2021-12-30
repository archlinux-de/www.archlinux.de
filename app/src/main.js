import Vue from 'vue'
import App from './App'
import router from './router'
import VueMeta from 'vue-meta'
import vueFilterPrettyBytes from 'vue-filter-pretty-bytes'
import createApiService from './services/ApiService'

Vue.use(VueMeta)
Vue.use(vueFilterPrettyBytes)

Vue.config.productionTip = false
new Vue({
  router,
  render: h => h(App),
  provide: {
    apiService: createApiService(fetch)
  }
}).$mount('#app')
