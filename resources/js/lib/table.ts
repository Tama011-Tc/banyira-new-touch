import { router } from '@inertiajs/vue3'

/** What the server applied to a table (see app/Support/TableQuery.php). */
export type TableState = { sort: string; dir: 'asc' | 'desc'; per: number; q?: string; [filter: string]: string | number | undefined }

/** Reloads a table with changed search, filters or order, starting again from the first page. */
export function useTableQuery(url: string, state: () => TableState) {
  const apply = (patch: Partial<TableState>) => {
    const next = Object.fromEntries(Object.entries({ ...state(), ...patch }).filter(([, v]) => v !== '' && v !== undefined))
    router.get(url, next as Record<string, string>, { preserveState: true, preserveScroll: true, replace: true })
  }
  const sortBy = (key: string) => apply({ sort: key, dir: state().sort === key && state().dir === 'asc' ? 'desc' : 'asc' })

  return { apply, sortBy }
}
