import Vue from 'vue'
import Router from 'vue-router'

import Download from './views/Download'
import Impressum from './views/Impressum'
import Mirrors from './views/Mirrors'
import News from './views/News'
import NewsItem from './views/NewsItem'
import Package from './views/Package'
import Packages from './views/Packages'
import PrivacyPolicy from './views/PrivacyPolicy'
import Release from './views/Release'
import Releases from './views/Releases'
import Start from './views/Start'
import NotFound from './views/NotFound'

Vue.use(Router)

export default new Router({
  mode: 'history',
  linkActiveClass: 'active',
  routes: [
    { path: '/download', name: 'download', component: Download },
    { path: '/impressum', name: 'impressum', component: Impressum },
    { path: '/mirrors', name: 'mirrors', component: Mirrors },
    { path: '/news', name: 'news', component: News },
    { path: '/news/:id-:slug', name: 'news-item', component: NewsItem },
    { path: '/news/:id', name: 'news-item-permalink', component: NewsItem },
    { path: '/packages/:repository/:architecture/:name', name: 'package', component: Package },
    { path: '/packages', name: 'packages', component: Packages },
    { path: '/privacy-policy', name: 'privacy-policy', component: PrivacyPolicy },
    { path: '/releases/:version', name: 'release', component: Release },
    { path: '/releases', name: 'releases', component: Releases },
    { path: '/', name: 'start', component: Start },
    { path: '*', component: NotFound }
  ],
  scrollBehavior (to, from, savedPosition) {
    return savedPosition ?? { x: 0, y: 0 }
  }
})
