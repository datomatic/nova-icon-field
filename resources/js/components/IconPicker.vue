<template>
  <Modal
    :show="true"
    class="max-w-2xl flex flex-col h-full relative fontawesome-modal bg-white border dark:bg-gray-800 rounded-lg shadow-lg border-gray-200 dark:border-gray-700 overflow-hidden"
    @close="handleClose"
  >
    <ModalHeader
      class="flex items-center px-6 py-6 border-b relative border-gray-200 dark:border-gray-700"
    >
      <div class="flex-1 mr-2">{{ __('novaIconField.modalTitle') }}</div>
      <a
        href="#"
        class="block text-gray-400 text-xs font-light mr-4 leading-none border border-gray-200 dark:border-gray-700 rounded p-1"
        @click.prevent="refresh"
      >
        {{ __('novaIconField.refreshButton') }}
      </a>
      <a
        href="#"
        class="block text-gray-400 text-xl leading-none"
        @click.prevent="handleClose"
      >
        &times;
      </a>
    </ModalHeader>

    <div
      class="flex py-4 mr-4 flex-wrap border-b border-gray-200 dark:border-gray-700"
    >
      <div class="w-1/2 px-4">
        <SelectControl
          v-model:selected="filter.style"
          class="w-full"
          :placeholder="__('All')"
          @change="filter.style = $event"
        >
          <option value disabled="disabled">
            {{ __('novaIconField.selectType.default') }}
          </option>
          <option value="all">
            {{ __('novaIconField.selectType.placeholder') }} ({{
              totalIconsCount
            }})
          </option>
          <option v-for="(count, style) in styles" :key="style" :value="style">
            {{ __(`novaIconField.types.${style}`, style) }} ({{ count }})
          </option>
        </SelectControl>
      </div>
      <div class="w-1/2 px-4">
        <input
          id="search"
          v-model="filter.search"
          type="text"
          class="w-full form-control form-input form-input-bordered"
          :placeholder="__('novaIconField.search.placeholder')"
        />
      </div>
    </div>

    <div
      id="iconContainer"
      class="flex-1 px-4 py-4 overflow-y-scroll"
      @scroll="onScroll"
    >
      <div v-if="isLoading" class="py-6 text-center text-md font-semibold">
        {{ __('novaIconField.loading') }}...
      </div>
      <div
        v-else-if="icons.length > 0 && !isLoading"
        class="flex flex-wrap items-stretch"
      >
        <div
          v-for="icon of iconsChunked"
          :key="`${icon.style}_${icon.icon}`"
          class="p-2 icon-box"
          @click="saveIcon(icon.style, icon.icon)"
        >
          <div
            class="rounded inner flex flex-col items-center justify-center text-center p-4 cursor-pointer"
          >
            <inline-svg
              :src="api.icon(icon.style, icon.icon)"
              class="fill-current dark:fill-gray-300 w-6 h-6"
              :aria-label="icon.icon"
            />
            <div
              class="text-xs leading-none mt-2 p-1 bg-gray-100 dark:bg-gray-700"
            >
              {{ icon.icon }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <ModalFooter class="flex justify-end">
      <div class="ml-auto">
        <CancelButton
          component="button"
          type="button"
          dusk="cancel-action-button"
          @click.prevent="handleClose"
        >
          {{ __('novaIconField.cancel') }}
        </CancelButton>
      </div>
    </ModalFooter>
  </Modal>
</template>

<script>
import InlineSvg from 'vue-inline-svg';
import HasIcon from '../mixins/HasIcon';

export default {
  name: 'IconPicker',
  components: {
    InlineSvg,
  },
  mixins: [HasIcon],
  props: ['field'],
  emits: ['confirm', 'close'],

  data: () => ({
    iconContainer: null,

    isLoading: false,

    styles: {},
    icons: [],

    iconsChunked: [],
    chunk: 0,
    expanded: false,

    filter: {
      style: 'all',
      search: '',
    },
  }),

  computed: {
    totalIconsCount() {
      return Object.values(this.styles).reduce((s, r) => r + s, 0);
    },
    filteredIcons() {
      let icons = [];
      for (const icon of this.icons) {
        if (this.applyFilter(icon.style, icon.icon)) {
          icons.push(icon);
        }
      }

      return icons;
    },
  },

  watch: {
    'filter.search': {
      handler(val) {
        this.filter.search = val;
        this.clearChunks();
        this.getChunk();
      },
    },
    'filter.style': {
      handler(val) {
        this.filter.style = val;
        this.clearChunks();
        this.getChunk();
      },
    },
  },

  beforeMount() {
    this.isLoading = true;
  },

  async mounted() {
    await this.loadIcons();

    this.iconContainer = document.querySelector('#iconContainer');

    this.$nextTick(() => {
      this.getChunk();
      this.isLoading = false;
    });
  },

  methods: {
    async refresh() {
      this.isLoading = true;

      this.styles = {};
      this.icons = [];
      await Nova.request().get(this.api.refresh(), {
        headers: this.novaHeaders,
      });

      this.clearFilter();
      await this.loadIcons();
      this.clearChunks();

      this.$nextTick(() => {
        this.getChunk();
        this.isLoading = false;
      });
    },

    async loadIcons() {
      const apiStyles = (
        await Nova.request().get(this.api.styles(), {
          headers: this.novaHeaders,
        })
      ).data;

      const styles = {};
      const icons = [];
      for (const style of apiStyles) {
        const styleIcons = (
          await Nova.request().get(this.api.icons(style), {
            headers: this.novaHeaders,
          })
        ).data;

        for (const icon of styleIcons) {
          if (this.canShowIcon(style, icon)) {
            styles[style] = (styles[style] ?? 0) + 1;
            icons.push({
              style,
              icon,
            });
          }
        }
      }
      if (icons.length > 0) {
        icons.sort((a, b) => (a.icon > b.icon ? 1 : b.icon > a.icon ? -1 : 0));
      }

      this.styles = styles;
      this.icons = icons;
    },
    canShowIcon(style, icon) {
      if (!style || !icon) {
        return false;
      }

      if (
        this.field.only &&
        !this.field.only.find((i) => {
          if (typeof i === 'string') {
            if (i === style || i === icon) {
              return true;
            }
            const [iS, iI] = this.getIconObject(i);
            return iS === style && iI === icon;
          }
          if (typeof i !== 'object') {
            return false;
          }
          if (!i['style'] && !i['icon']) {
            return false;
          }

          return (
            (!i['style'] || i['style'] === style) &&
            (!i['icon'] || i['icon'] === icon)
          );
        })
      ) {
        return false;
      }

      return true;
    },

    onScroll({ target: { scrollTop, clientHeight, scrollHeight } }) {
      if (
        scrollTop + clientHeight >= scrollHeight - 250 &&
        this.expanded === false
      ) {
        this.expanded = true;
        this.getChunk();
      }
    },
    getChunk() {
      let chunkSize = 100;

      let nextChunk = this.filteredIcons.slice(
        this.chunk,
        this.chunk + chunkSize
      );
      this.iconsChunked = [...this.iconsChunked, ...nextChunk];

      this.expanded = false;
      this.chunk += chunkSize;
    },
    clearChunks() {
      this.chunk = 0;
      this.iconsChunked = [];
      this.iconContainer.scrollTop = 0;
    },

    applyFilter(style, icon) {
      return (
        this.applyStyleFilter(style, icon) &&
        this.applySearchFilter(style, icon)
      );
    },
    applySearchFilter(style, icon) {
      let keyword = this.filter.search?.trim().toUpperCase();
      if (!keyword) {
        return true;
      }

      let name = icon.toUpperCase();

      let alt = keyword.replace('-', ' ');
      let nameAlt = name.replace('-', ' ');

      return name.includes(keyword) || nameAlt.includes(alt);
    },
    applyStyleFilter(style, icon) {
      return (
        !this.filter.style ||
        this.filter.style === 'all' ||
        this.filter.style === style
      );
    },

    clearFilter() {
      this.filter.style = 'all';
      this.filter.search = '';
      this.clearChunks();
    },

    saveIcon(style, icon) {
      this.clearFilter();
      this.$emit('confirm', style, icon);
    },

    handleClose() {
      this.clearFilter();
      this.$emit('close');
    },
  },
};
</script>

<style scoped>
.icon-box {
  width: 20%;
  height: 100%;
}

.icon-box > div {
  outline: 1px solid rgba(var(--colors-gray-300));
}

.dark .icon-box > div {
  outline: 1px solid rgba(var(--colors-gray-700));
}

.icon-box > div:hover {
  outline: 1px solid rgba(var(--colors-primary-500));
}
.icon-box > div:hover svg {
  fill: rgba(var(--colors-primary-500));
}

@media (max-width: 1279px) {
  .icon-box {
    width: 25%;
  }
}

@media (max-width: 900px) {
  .icon-box {
    width: 50%;
  }
}
</style>
