import { inject, type InjectionKey } from 'vue'

/** One section of a public page, as stored. Fields depend on the type (see BlockCatalogue.php). */
export type Block = { id: string; type: string; visible: boolean; data: Record<string, any> }

export type PageImage = { id: number; url: string; alt: string; width: number; height: number }

export type PageContext = {
  /** Live information (products, rules, minimum offer) used by some blocks. */
  live: Record<string, any>
  media: Record<number, PageImage>
  /** True in the staff draft preview, where forms must not send anything. */
  preview: boolean
}

export const pageContextKey: InjectionKey<PageContext> = Symbol('page-context')

export const usePageContext = () => inject(pageContextKey, { live: {}, media: {}, preview: false })

/** Links to this site are followed inside the app; anything else leaves it. */
export const isInternal = (href: string) => href.startsWith('/')

/** Blank lines separate paragraphs. */
export const paragraphs = (text: string) => text.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean)
