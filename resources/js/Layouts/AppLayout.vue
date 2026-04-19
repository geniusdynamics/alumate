<script lang="ts">
/**
 * AppLayout (Layouts/AppLayout.vue)
 *
 * This is a canonical re-export shim.
 * The real implementation lives in @/Layouts/DefaultLayout.vue.
 *
 * All pages importing AppLayout receive the same DefaultLayout component,
 * eliminating the redundant wrapper indirection while keeping all existing
 * import paths working without any page-level changes.
 */
export { default } from '@/Layouts/DefaultLayout.vue';
</script>
