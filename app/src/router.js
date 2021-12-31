import { createRouter, createWebHistory } from 'vue-router'
import NotFound from './views/NotFound'

export default createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes: [
    { path: '/download', name: 'download', component: () => import(/* webpackChunkName: "releases" */ './views/Download') },
    { path: '/impressum', name: 'impressum', component: () => import(/* webpackChunkName: "legal" */ './views/Impressum') },
    { path: '/mirrors', name: 'mirrors', component: () => import(/* webpackChunkName: "mirrors" */ './views/Mirrors') },
    { path: '/news', name: 'news', component: () => import(/* webpackChunkName: "news" */ './views/News') },
    { path: '/news/:id-:slug', name: 'news-item', component: () => import(/* webpackChunkName: "news" */ './views/NewsItem') },
    { path: '/news/:id', name: 'news-item-permalink', component: () => import(/* webpackChunkName: "news" */ './views/NewsItem') },
    { path: '/packages/:repository/:architecture/:name', name: 'package', component: () => import(/* webpackChunkName: "packages" */ './views/Package') },
    { path: '/packages', name: 'packages', component: () => import(/* webpackChunkName: "packages" */ './views/Packages') },
    { path: '/privacy-policy', name: 'privacy-policy', component: () => import(/* webpackChunkName: "legal" */ './views/PrivacyPolicy') },
    { path: '/releases/:version', name: 'release', component: () => import(/* webpackChunkName: "releases" */ './views/Release') },
    { path: '/releases', name: 'releases', component: () => import(/* webpackChunkName: "releases" */ './views/Releases') },
    { path: '/', name: 'start', component: () => import(/* webpackChunkName: "start" */ './views/Start') },
    { path: '/:pathMatch(.*)*', component: NotFound }
  ],
  scrollBehavior (to, from, savedPosition) {
    return savedPosition ?? { x: 0, y: 0 }
  }
})
