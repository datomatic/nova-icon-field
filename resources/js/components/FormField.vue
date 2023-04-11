<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
  >
    <template #field>
      <div>
        <div v-if="style && icon" class="display-icon mb-4 flex items-center">
          <div
            class="relative p-8 border border-gray-300 dark:border-gray-700 rounded"
          >
            <inline-svg
              :src="api.icon(style, icon)"
              class="fill-current dark:fill-gray-300 w-6 h-6"
              :aria-label="icon"
            />

            <span
              class="absolute top-0 right-0 rounded-full text-center bg-gray-200 dark:bg-gray-700 close-icon"
              @click="() => clear(enforceDefaultIcon)"
            >
              &times;
            </span>
          </div>
          <div class="ml-2 text-sm text-gray-400 dark:text-gray-600">
            {{ value }}
          </div>
        </div>

        <DefaultButton type="button" @click.prevent="openModal">
          {{ addButtonText }}
        </DefaultButton>

        <IconPicker
          v-if="modalOpen"
          class="max-w-3xl"
          :field="currentField"
          @confirm="confirmModal"
          @close="closeModal"
        />
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from 'laravel-nova';
import HasIcon from '../mixins/HasIcon';
import InlineSvg from 'vue-inline-svg';
import IconPicker from './IconPicker.vue';

export default {
  components: {
    IconPicker,
    InlineSvg,
  },
  mixins: [DependentFormField, HandlesValidationErrors, HasIcon],

  props: ['resourceName', 'resourceId', 'field'],

  data() {
    return {
      style: null,
      icon: null,
      modalOpen: false,
      value: null,
    };
  },

  methods: {
    setInitialValue() {
      if (this.currentField.value) {
        [this.style, this.icon] = this.getIconObject(this.currentField.value);
        this.value = this.style && this.icon ? this.currentField.value : null;
      } else {
        this.clear(true);
      }
    },

    openModal() {
      this.modalOpen = true;
    },
    confirmModal(style, icon) {
      this.style = style;
      this.icon = icon;
      this.value = this.getIconValue(style, icon);
      this.modalOpen = false;
    },
    closeModal() {
      this.modalOpen = false;
    },

    clear(enforceDefaultIcon) {
      if (enforceDefaultIcon && this.defaultIcon && this.defaultIconStyle) {
        this.value = this.getIconValue(this.defaultIconStyle, this.defaultIcon);
        this.style = this.defaultIconStyle;
        this.icon = this.defaultIcon;
      } else {
        this.value = null;
        this.style = null;
        this.icon = null;
      }
    },

    fill(formData) {
      this.fillIfVisible(
        formData,
        this.currentField.attribute,
        this.value ?? ''
      );
    },

    handleChange(value) {
      this.value = value;
    },
  },
};
</script>

<style>
.display-icon:hover .close-icon {
  display: block;
}

.close-icon {
  display: none;

  opacity: 0.75;
  cursor: pointer;

  transform: translate(50%, -50%);

  line-height: 1;
  width: 1rem;
  height: 1rem;
  font-size: 1rem;
}

.close-icon:hover {
  opacity: 1;
}
</style>
