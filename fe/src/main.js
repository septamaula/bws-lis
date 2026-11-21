import './style.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import DataTable from '@/components/DataTable.vue';
import DetailTable from '@/components/DetailTable.vue';
import SubpageHeader from '@/components/SubpageHeader.vue';
import AppButton from '@/components/AppButton.vue';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.component('DataTable', DataTable);
app.component('DetailTable', DetailTable);
app.component('SubpageHeader', SubpageHeader);
app.component('AppButton', AppButton);
app.component('BaseButton', AppButton);

app.mount('#app');

