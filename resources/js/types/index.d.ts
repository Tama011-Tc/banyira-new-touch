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

export type SharedProps = {
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
