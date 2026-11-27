import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Protects unsaved editing: asks before leaving, and keeps a copy in this
 * browser so work survives the staff session timing out. The copy is only
 * offered back if nobody has saved a newer revision.
 */
export function useUnsaved<T>(key: () => string, revision: () => number, value: () => T, dirty: () => boolean) {
  const read = (): T | null => {
    try {
      const stored = JSON.parse(localStorage.getItem(key()) ?? 'null') as { revision: number; value: T } | null
      return stored && stored.revision === revision() ? stored.value : null
    } catch { return null }
  }
  const recovered = ref<T | null>(null)
  const forget = () => { recovered.value = null; try { localStorage.removeItem(key()) } catch { /* storage unavailable */ } }

  const beforeUnload = (e: BeforeUnloadEvent) => { if (dirty()) e.preventDefault() }
  let off: (() => void) | undefined

  onMounted(() => {
    const copy = read()
    if (copy && JSON.stringify(copy) !== JSON.stringify(value())) recovered.value = copy as any
    window.addEventListener('beforeunload', beforeUnload)
    off = router.on('before', (e) => {
      if (e.detail.visit.method === 'get' && dirty() && !window.confirm('Leave without saving your changes?')) e.preventDefault()
    })
    watch(value, (v) => {
      if (!dirty()) return
      try { localStorage.setItem(key(), JSON.stringify({ revision: revision(), value: v })) } catch { /* storage unavailable */ }
    }, { deep: true })
  })
  onBeforeUnmount(() => { window.removeEventListener('beforeunload', beforeUnload); off?.() })

  return { recovered, forget }
}
