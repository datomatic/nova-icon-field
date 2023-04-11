<template>
  <PanelItem :field="field">
    <template #value>
      <inline-svg
        :src="api.icon(...getIconObject(field.value))"
        class="fill-current dark:fill-gray-300 w-6 h-6"
        :aria-label="field.value"
      />
    </template>
  </PanelItem>
</template>

<script>
import InlineSvg from 'vue-inline-svg';
import HasIcon from '../mixins/HasIcon';

export default {
  components: { InlineSvg },
  mixins: [HasIcon],
  props: ['resource', 'resourceName', 'resourceId', 'field'],
};
</script>
