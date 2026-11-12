import { reactive } from 'vue'

// Shared state between a page's large title and the app chrome: when the large
// title scrolls out of view, the top bar shows it compactly (iOS large titles).
export const chrome = reactive({ title: '', collapsed: false })
