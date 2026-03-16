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
          @change="filter.style = $event.target.value"
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
      <template v-else>
        <div
          v-if="icons.length > 0"
          class="flex flex-wrap items-stretch"
        >
          <div
            v-for="icon of icons"
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
        <div v-else class="py-6 text-center text-md font-semibold text-gray-400">
          {{ __('novaIconField.noResults') }}
        </div>
        <div v-if="isLoadingMore" class="py-4 text-center text-sm text-gray-400">
          {{ __('novaIconField.loading') }}...
        </div>
      </template>
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
    isLoadingMore: false,

    styles: {},
    icons: [],

    currentPage: 1,
    hasMore: false,
    totalCount: 0,
    searchDebounceTimer: null,

    filter: {
      style: 'all',
      search: '',
    },
  }),

  computed: {
    totalIconsCount() {
      return Object.values(this.styles).reduce((s, r) => r + s, 0);
    },
  },

  watch: {
    'filter.search': {
      handler() {
        clearTimeout(this.searchDebounceTimer);
        this.searchDebounceTimer = setTimeout(() => {
          this.fetchIcons(true);
        }, 300);
      },
    },
    'filter.style': {
      handler() {
        this.fetchIcons(true);
      },
    },
  },

  beforeMount() {
    this.isLoading = true;
  },

  async mounted() {
    this.iconContainer = document.querySelector('#iconContainer');
    await this.fetchIcons(true);
  },

  beforeUnmount() {
    clearTimeout(this.searchDebounceTimer);
  },

  methods: {
    async fetchIcons(reset = false) {
      if (reset) {
        this.currentPage = 1;
        this.icons = [];
        this.isLoading = true;
        if (this.iconContainer) {
          this.iconContainer.scrollTop = 0;
        }
      } else {
        this.isLoadingMore = true;
      }

      try {
        const params = {
          page: this.currentPage,
          per_page: 100,
          search: this.filter.search || '',
          style: this.filter.style || 'all',
        };

        if (this.field.only && this.field.only.length > 0) {
          params.only = JSON.stringify(this.field.only);
        }

        const response = await Nova.request().get(this.api.search(), {
          headers: this.novaHeaders,
          params,
        });

        const { data, meta, styles } = response.data;

        if (reset) {
          this.icons = data;
        } else {
          this.icons = [...this.icons, ...data];
        }

        if (Object.keys(this.styles).length === 0) {
          this.styles = styles;
        }
        this.hasMore = meta.has_more;
        this.totalCount = meta.total;
      } finally {
        this.isLoading = false;
        this.isLoadingMore = false;
      }
    },

    loadNextPage() {
      if (!this.hasMore || this.isLoadingMore) {
        return;
      }
      this.currentPage++;
      this.fetchIcons(false);
    },

    async refresh() {
      this.isLoading = true;
      this.styles = {};
      this.icons = [];

      await Nova.request().get(this.api.refresh(), {
        headers: this.novaHeaders,
      });

      this.filter.style = 'all';
      this.filter.search = '';
      await this.fetchIcons(true);
    },

    onScroll({ target: { scrollTop, clientHeight, scrollHeight } }) {
      if (scrollTop + clientHeight >= scrollHeight - 250 && !this.isLoadingMore) {
        this.loadNextPage();
      }
    },

    saveIcon(style, icon) {
      this.filter.style = 'all';
      this.filter.search = '';
      this.$emit('confirm', style, icon);
    },

    handleClose() {
      this.filter.style = 'all';
      this.filter.search = '';
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
