import IndexField from './components/IndexField.vue';
import DetailField from './components/DetailField.vue';
import FormField from './components/FormField.vue';

Nova.booting((Vue, router) => {
  Vue.component('IndexNovaIconField', IndexField);
  Vue.component('DetailNovaIconField', DetailField);
  Vue.component('FormNovaIconField', FormField);
});
