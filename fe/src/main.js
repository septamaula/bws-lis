import './style.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import DataTable from '@/components/DataTable.vue';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.component('DataTable', DataTable);

app.mount('#app');

