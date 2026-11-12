import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Employee permission slugs. Keep in sync with ReferenceDataSeeder::PERMISSIONS;
 * the union makes a mistyped slug a type error instead of a silently hidden link.
 * The server remains the authority: routes are guarded by `can:` middleware,
 * and this only decides what the interface offers.
 */
export type Permission =
  | 'applications.approve' | 'applications.assess' | 'applications.view'
  | 'audit.view' | 'campaigns.manage' | 'customers.verify' | 'executive.dashboard'
  | 'finance.approve' | 'finance.record' | 'finance.view'
  | 'leads.manage' | 'listings.publish' | 'marketing.reports'
  | 'payments.reconcile' | 'payroll.manage' | 'payroll.view'
  | 'products.manage' | 'reports.view' | 'roles.manage'
  | 'rules.approve' | 'rules.propose'
  | 'security.view' | 'system.manage' | 'users.manage'

/** Returns `can(permission)` for the signed-in user. Reactive across Inertia visits. */
export function useCan() {
  const page = usePage()
  const granted = computed(() => new Set(page.props.auth.user?.permissions ?? []))
  return (permission: Permission) => granted.value.has(permission)
}
