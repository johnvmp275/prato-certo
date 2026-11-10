import './assets/css/reset.css';
import './assets/css/colors.css';
import './assets/css/global.css';
import './assets/css/typography.css';
import './assets/css/scrollbar.css';

import { createApp } from 'vue';
import { createHead } from '@unhead/vue/client';
import { createPinia } from 'pinia';

import router from './router';

import App from './App.vue';

import { setupAxios } from './plugins/axios';

const app = createApp(App);
const pinia = createPinia();
const head = createHead();

app.use(pinia);
app.use(router);
app.use(head);

setupAxios(router);

app.mount('#app');
