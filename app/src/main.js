import Vue from 'vue'
import App from './App'
import router from './router'
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
  PaginationPlugin,
  TablePlugin,
  SpinnerPlugin
} from 'bootstrap-vue'
import vueFilterPrettyBytes from 'vue-filter-pretty-bytes'
import createApiService from './services/ApiService'

Vue.use(VueMeta)
Vue.use(LayoutPlugin)
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
Vue.use(SpinnerPlugin)

Vue.config.productionTip = false
new Vue({
  router,
  render: h => h(App),
  provide: {
    apiService: createApiService(fetch)
  }
}).$mount('#app')
