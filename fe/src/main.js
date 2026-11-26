import './style.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import DataTable from '@/components/DataTable.vue';
import DetailTable from '@/components/DetailTable.vue';
import SubpageHeader from '@/components/SubpageHeader.vue';
import AppButton from '@/components/AppButton.vue';
import AppNumberInput from '@/components/AppNumberInput.vue';
import AppSearchableSelect from '@/components/AppSearchableSelect.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { i18nPlugin } from '@/i18n';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(i18nPlugin);
app.component('DataTable', DataTable);
app.component('DetailTable', DetailTable);
app.component('SubpageHeader', SubpageHeader);
app.component('AppButton', AppButton);
app.component('BaseButton', AppButton);
app.component('AppNumberInput', AppNumberInput);
app.component('NumberInput', AppNumberInput);
app.component('AppSearchableSelect', AppSearchableSelect);
app.component('SearchableSelect', AppSearchableSelect);
app.component('LanguageSwitcher', LanguageSwitcher);

app.mount('#app');

