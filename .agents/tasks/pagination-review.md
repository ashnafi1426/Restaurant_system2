# Hotel Admin Management Pagination and Status Filter Fix

This commit addresses pagination control visibility issues and missing status filter functionality in the Hotel Admin Management interface. The changes ensure administrators can filter by user status (active/inactive) and access pagination controls even when results fit on a single page.

**Watch for:** **confirmed** status filter edge cases in the service layer filtering logic.

**Verdict**: APPROVED

## High-level view

The Vue frontend now properly passes the `selectedStatus` parameter to the backend and watches it for changes, fixing the non-functional status dropdown. Pagination controls are now visible whenever there are items to display, allowing users to change items-per-page even with single-page results. The backend controller accepts the status parameter and passes it to the service layer, which applies the correct boolean filtering based on string values.

<details>
<summary>Issues (1)</summary>

1. **Status filtering edge cases** — The service uses simple string equality checks ("active"/"inactive") but doesn't handle unexpected values or case variations that could bypass filtering.

</details>

<details><summary>Details</summary>

## Status filter integration via selectedStatus parameter

The Vue component's `selectedStatus` reactive property was not being passed to the API call in `loadAdmins()`. The fix adds `status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined` to the parameter object sent to `platformService.getAllAdmins()`. The backend controller now accepts this parameter and forwards it to the service layer.

The service filtering logic maps string values to boolean database queries: "active" becomes `where('is_active', true)` and "inactive" becomes `where('is_active', false)`. This approach works for the expected values but doesn't handle edge cases like mixed-case strings or unexpected values, which would silently bypass the filter.

## Pagination controls visibility fix

The outer pagination container condition changed from `v-if="lastPage > 1"` to `v-if="totalItems > 0"`, making pagination controls visible whenever there are items to display. The inner navigation controls (`v-if="totalPages > 1"`) and jump controls maintain their conditional visibility for multi-page scenarios only.

</details>

## File map

<details>
<summary>Files changed</summary>

- `Client2/vue-project/src/views/Admin/hotels/HotelAdminManagementView.vue` — Fixed status parameter passing, watch array, and pagination visibility logic with enhanced controls
- `server/app/Http/Controllers/Api/Platform/PlatformHotelController.php` — Added status parameter acceptance in allAdmins method
- `server/app/Services/Platform/PlatformHotelService.php` — Added is_active filtering based on status string parameter
- `Client2/vue-project/src/views/Admin/hotels/PlatformUsersView.vue` — Similar pagination enhancements (unrelated to hotel admin issue)
- `server/app/Http/Controllers/Api/WaiterController.php` — File deleted (unrelated cleanup)

View full diff: `git show HEAD`

</details>