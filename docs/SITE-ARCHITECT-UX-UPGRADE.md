# Site Architect UX Upgrade

## Left sidebar navigation

- Replaces the horizontal tab strip inside `x-site-architect.workspace`.
- Groups: **Content**, **Sections**, **Deploy**, **Advanced**, **Operations** (permission-filtered).
- Expand/collapse per group with `+` / `−`; state stored in `localStorage` key `medca.site-architect.sidebar.expanded`.
- Mobile drawer via **Site Architect menu** button (lg breakpoint).
- All route names, labels, and screens are unchanged.

Configuration: `App\Support\SiteArchitectNavigation::sidebarGroups()`.

## Universal bulk selection

Trait: `App\Livewire\Concerns\InteractsWithBulkSelection`

- Row checkbox, select all visible, select all filtered, deselect all.
- Selection persists while paginating (IDs held on the Livewire component).

Applied to:

- Pages (`site_architect.pages`)
- Blogs (`site_architect.blogs`)
- Blocks Factory (`site_architect.blocks`)

Registry: `config/bulk_actions.php`

## Universal bulk actions

Trait: `App\Livewire\Concerns\InteractsWithBulkActions`  
Service: `App\Services\Bulk\BulkActionService`  
Preview: `App\Services\Bulk\BulkGovernancePreview`

Destructive actions show:

- Selected count, affected pages, registry rows, mappings, URLs, location/service pages, cascading deletions.
- Confirmation modal with **type DELETE** for irreversible deletes.

Components:

- `resources/views/components/bulk/selection-toolbar.blade.php`
- `resources/views/components/bulk/action-modal.blade.php`

Export route: `GET site-architect/bulk/export` (`site-architect.bulk.export`).

## Drag-and-drop reorder

JS: `resources/js/site-architect-sortable.js` (SortableJS, same pattern as Navigation).

Applied to:

- Pages section builder (`syncContentPartsOrder`)
- Blogs section builder (`syncContentPartsOrder`)
- Navigation (existing `site-architect-navigation.js`)

Markup contract:

```html
<ul data-sortable-list data-sortable-method="syncContentPartsOrder" …>
  <li data-sortable-item="0">
    <button data-sortable-handle>⋮⋮</button>
```

Up / Down / Remove buttons remain as fallback.

## Tests

`tests/Feature/SiteArchitectUxUpgradeTest.php`
