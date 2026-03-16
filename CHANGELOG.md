# Changelog

All notable changes to this project will be documented in this file.

## [2.0.0] - 2026-03-16

### Breaking Changes
- Upgraded to **Laravel Nova 5** (dropped Nova 4 support)
- Vue component registration updated from `Vue.component()` to `app.component()` (Vue 3 / Nova 5 API)
- Icon picker now uses a **server-side search endpoint** instead of client-side filtering — the `styles` and `icons` endpoints are no longer called by the picker

### Added
- New `search` endpoint with server-side filtering, pagination, and `only` rules support
- Server-side cached master index of all icons for fast search
- Infinite scroll pagination (100 icons per page)
- Debounced search input (300ms)
- "No icons found" empty state message
- "Loading more" indicator during pagination
- Translations for `novaIconField.noResults` (EN, IT)

### Fixed
- Style select `@change` event handler (`$event` → `$event.target.value`)
- Replaced `DefaultButton` with plain `<button>` for Nova 5 compatibility

### Removed
- Client-side icon loading, filtering, and chunking logic

## [1.0.2]

### Fixed
- Replaced `TecnobitController` dependency with `Illuminate\Routing\Controller` to make the package standalone

## [1.0.1]

### Fixed
- Fixed wrong path for routes directory (`Routes/` → `routes/`)
- Fixed wrong path for config directory (`config/` → `../config/`)

## [1.0.0]

- Initial release
