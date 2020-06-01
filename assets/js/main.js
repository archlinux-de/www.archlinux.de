import Vue from 'vue'
import VueMeta from 'vue-meta'

import {
  AlertPlugin,
  ButtonPlugin,
  FormGroupPlugin,
  FormInputPlugin,
  FormPlugin,
  FormSelectPlugin,
  InputGroupPlugin,
  JumbotronPlugin,
  LayoutPlugin,
  NavbarPlugin,
  PaginationPlugin,
  TablePlugin
} from 'bootstrap-vue'

import App from './App'
import router from './router'
import vueFilterPrettyBytes from 'vue-filter-pretty-bytes'
import createApiService from './services/ApiService'

Vue.config.productionTip = false
Vue.use(VueMeta)

Vue.use(LayoutPlugin)
Vue.use(NavbarPlugin)
Vue.use(ButtonPlugin)
Vue.use(JumbotronPlugin)
Vue.use(FormPlugin)
Vue.use(FormInputPlugin)
Vue.use(FormGroupPlugin)
Vue.use(FormSelectPlugin)
Vue.use(TablePlugin)
Vue.use(AlertPlugin)
Vue.use(PaginationPlugin)
Vue.use(InputGroupPlugin)

Vue.use(vueFilterPrettyBytes)

new Vue({
  router,
  render: h => h(App),
  provide: {
    apiService: createApiService(fetch)
  }
}).$mount('#app')
