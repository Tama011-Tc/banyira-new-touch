import type { Block, PageImage } from './blocks'

/** A field definition from app/Services/Content/BlockCatalogue.php. */
export type BlockField = {
  key: string
  label: string
  kind: 'text' | 'textarea' | 'boolean' | 'select' | 'media' | 'items'
  required: boolean
  max?: number
  format?: 'link' | 'anchor'
  options?: { value: string; label: string }[]
  itemLabel?: string
  fields?: BlockField[]
}

export type BlockType = { label: string; description: string; fields: BlockField[] }

export type PageContent = {
  title: string
  description: string
  nav: { header: boolean; header_label: string; footer: string; footer_label: string; order: number }
  blocks: Block[]
}

export type MediaOption = PageImage

/** Moves one entry of a list up or down, returning a new list. */
export function moved<T>(list: T[], index: number, by: 1 | -1): T[] {
  const to = index + by
  if (to < 0 || to >= list.length) return list
  const next = [...list]
  ;[next[index], next[to]] = [next[to], next[index]]
  return next
}

/** Blank values for a new section or list entry. A required list starts with one entry to fill in. */
export function emptyData(fields: BlockField[]): Record<string, any> {
  return Object.fromEntries(fields.map((f) => {
    if (f.kind === 'boolean') return [f.key, false]
    if (f.kind === 'select') return [f.key, f.options![0].value]
    if (f.kind === 'media') return [f.key, null]
    if (f.kind === 'items') return [f.key, f.required ? [emptyData(f.fields!)] : []]
    return [f.key, '']
  }))
}

export function newBlock(type: string, definition: BlockType): Block {
  return { id: `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`, type, visible: true, data: emptyData(definition.fields) }
}

/** A short line that identifies a section in the editor's list. */
export function summary(block: Block): string {
  const d = block.data
  const first = d.heading || d.title || d.headline || d.items?.[0]?.title || d.items?.[0]?.question || d.phases?.[0]?.title || d.panels?.[0]?.title || d.body
  return typeof first === 'string' ? first.slice(0, 80) : ''
}
