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
/** The theme actually showing, after 'system' is resolved. */
export const isDark = ref(typeof window !== 'undefined' && resolve(theme.value) === 'dark')

const apply = () => {
  const root = document.documentElement
  // Ease the switch instead of flashing (removed again right after).
  root.classList.add('theme-switching')
  root.dataset.theme = resolve(theme.value)
  isDark.value = root.dataset.theme === 'dark'
  window.setTimeout(() => root.classList.remove('theme-switching'), 320)
}

export function setTheme(p: ThemePref) {
  theme.value = p
  try { localStorage.setItem(KEY, p) } catch { /* private mode */ }
  apply()
}

/** Flip to the opposite of what is showing; the result is an explicit choice. */
export const toggleTheme = () => setTheme(isDark.value ? 'light' : 'dark')

media?.addEventListener('change', () => theme.value === 'system' && apply())
