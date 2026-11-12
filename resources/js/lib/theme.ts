import { ref } from 'vue'

// Appearance: 'system' follows the device; 'light' / 'dark' are explicit
// choices. The resolved theme is always written to <html data-theme>, which
// the design tokens and the `dark:` variant read. The same logic runs inline
// in app.blade.php before first paint, so there is no flash.
export type ThemePref = 'system' | 'light' | 'dark'
const KEY = 'banyira-theme'

const read = (): ThemePref => {
  try { return (localStorage.getItem(KEY) as ThemePref) || 'system' } catch { return 'system' }
}
const media = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null
const resolve = (p: ThemePref) => (p === 'system' ? (media?.matches ? 'dark' : 'light') : p)

export const theme = ref<ThemePref>(typeof window !== 'undefined' ? read() : 'system')

const apply = () => {
  const root = document.documentElement
  // Ease the switch instead of flashing (removed again right after).
  root.classList.add('theme-switching')
  root.dataset.theme = resolve(theme.value)
  window.setTimeout(() => root.classList.remove('theme-switching'), 320)
}

export function setTheme(p: ThemePref) {
  theme.value = p
  try { localStorage.setItem(KEY, p) } catch { /* private mode */ }
  apply()
}

media?.addEventListener('change', () => theme.value === 'system' && apply())
