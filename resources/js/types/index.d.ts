export type AuthUser = {
  id: number
  name: string
  email: string
  type: 'business' | 'investor' | 'employee'
  permissions: string[]
  role?: string | null
}

export type Paginated<T> = {
  data: T[]
  links: { url: string | null; label: string; active: boolean }[]
  current_page: number
  last_page: number
  total: number
  from: number | null
  to: number | null
}

export type NavLink = { href: string; label: string }

/** Website wording and page links, shared on public pages only. */
export type Site = {
  settings: {
    announcement: { enabled: boolean; text: string; link: string }
    footer_about: string
    footer_location: string
    footer_notice: string
    risk_statement: string
  }
  nav: { header: NavLink[]; footer: Record<'businesses' | 'investors' | 'company', NavLink[]> }
}

export type SharedProps = {
  navProducts: { slug: string; name: string; summary: string }[]
  site: Site | null
  auth: { user: AuthUser | null }
  flash: { success?: string | null; error?: string | null }
  counts: { messages: number; notifications: number }
  demoPayments: boolean
}

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: SharedProps
  }
}
