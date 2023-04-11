<template>
  <inline-svg
    :src="api.icon(...getIconObject(field.value))"
    class="fill-current dark:fill-gray-300 w-4 h-4"
    :aria-label="field.value"
  />
</template>

<script>
import HasIcon from '../mixins/HasIcon';
import InlineSvg from 'vue-inline-svg';

export default {
  components: { InlineSvg },
  mixins: [HasIcon],
  props: ['resourceName', 'field'],
};
</script>
