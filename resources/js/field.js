import IndexField from './components/IndexField.vue';
import DetailField from './components/DetailField.vue';
import FormField from './components/FormField.vue';

Nova.booting((app, store) => {
  app.component('IndexNovaIconField', IndexField);
  app.component('DetailNovaIconField', DetailField);
  app.component('FormNovaIconField', FormField);
});
