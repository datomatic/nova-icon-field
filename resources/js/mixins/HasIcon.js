export default {
  computed: {
    defaultIcon() {
      return this.field.default_icon || null;
    },
    defaultIconStyle() {
      return this.field.default_icon_style || null;
    },
    addButtonText() {
      return this.field.add_button_text || this.__('novaIconField.addIcon');
    },
    enforceDefaultIcon() {
      return this.field.enforce_default_icon || false;
    },
    stylePrefix() {
      return this.field.style_prefix || '';
    },
    styleSuffix() {
      return this.field.style_suffix || '';
    },
    iconPrefix() {
      return this.field.icon_prefix || '';
    },
    iconSuffix() {
      return this.field.icon_suffix || '';
    },
    api() {
      return {
        styles: () => this.field.styles_url,
        icons: (style) =>
          decodeURI(this.field.icons_url).replace('[style]', style),
        icon: (style, icon) => {
          let url = decodeURI(this.field.icon_url)
            .replace('[style]', style)
            .replace('[icon]', icon);

          let sep = url.includes('?') ? '&' : '?';
          for (const [name, value] of Object.entries(this.iconParams)) {
            url += `${sep}${name}=${value}`;
            sep = '&';
          }
          return url.toString();
        },
        refresh: () => this.field.refresh_url,
      };
    },
    novaHeaders() {
      return this.field.api_nova_headers ?? {};
    },
    iconParams() {
      return this.field.api_icon_params ?? {};
    },
  },
  methods: {
    getIconValue(style, icon) {
      if (!style || !icon) {
        return null;
      }
      return `${this.stylePrefix}${style}${this.styleSuffix} ${this.iconPrefix}${icon}${this.iconSuffix}`;
    },
    getIconObject(value) {
      let [style, icon] = value.split(' ');
      if (!style || !icon) {
        return [null, null];
      }

      style = style.replace(new RegExp(`^${this.stylePrefix}`), '');
      style = style.replace(new RegExp(`${this.styleSuffix}$`), '');
      icon = icon.replace(new RegExp(`^${this.iconPrefix}`), '');
      icon = icon.replace(new RegExp(`${this.iconSuffix}$`), '');
      return [style, icon];
    },
  },
};
