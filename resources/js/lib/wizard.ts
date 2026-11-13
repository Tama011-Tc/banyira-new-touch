import { computed, ref } from 'vue'

// One question per screen. A step names the form fields it owns (so a server
// error can send the user back to the right screen) and says when it is answered.
export type WizardStep = { key: string; title: string; sub?: string; fields: string[]; done: () => boolean }

export const filled = (v: unknown) => v !== null && v !== undefined && String(v).trim() !== ''

/** `range` is the slice of the whole sign-up journey these steps cover, in percent. */
export function useWizard(steps: WizardStep[], range: [number, number] = [0, 100]) {
  const index = ref(0)
  const dir = ref<'next' | 'back'>('next')
  const go = (i: number) => {
    if (i < 0 || i >= steps.length || i === index.value) return
    dir.value = i < index.value ? 'back' : 'next'
    index.value = i
  }

  return {
    index,
    dir,
    count: steps.length,
    step: computed(() => steps[index.value]),
    first: computed(() => index.value === 0),
    last: computed(() => index.value === steps.length - 1),
    progress: computed(() => range[0] + (index.value / steps.length) * (range[1] - range[0])),
    next: () => go(index.value + 1),
    back: () => go(index.value - 1),
    showErrors: (errors: Record<string, unknown>) => go(steps.findIndex((s) => s.fields.some((f) => f in errors))),
  }
}
