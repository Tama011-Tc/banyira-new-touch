<script setup lang="ts">
import { computed, type Component } from 'vue'
import {
  Activity, ArrowLeft, ArrowLeftRight, ArrowRight, BadgeCheck, Banknote, Bell, BookOpen, Briefcase, Building2, CalendarClock, ChartColumn,
  Check, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, CircleCheck, CircleHelp, ClipboardCheck, Download, Ellipsis, FilePen, FileSpreadsheet,
  FileText, Gauge, HandCoins, History, House, Inbox, Info, KeyRound, Landmark, LayoutGrid, Lock, LogOut, Megaphone, Menu, MessageCircle, Monitor,
  Moon, Package, Paperclip, PiggyBank, Plus, Receipt, Scale, ScrollText, Search, Server, Settings, Shield, ShieldCheck, SlidersHorizontal, Store,
  Sun, Target, TrendingUp, TriangleAlert, User, UserCog, Users, Wallet, X, ArrowDown, ArrowUp, Database, Eye, EyeOff, Globe, Image, Trash2,
} from 'lucide-vue-next'

// One icon set (Lucide), one stroke weight. Names are Banyira's own so pages
// never depend on the library's naming.
const MAP: Record<string, Component> = {
  check: Check, x: X, menu: Menu, more: Ellipsis, arrow: ArrowRight, back: ArrowLeft, chev: ChevronRight, chevleft: ChevronLeft, chevdown: ChevronDown,
  search: Search, alert: TriangleAlert, error: CircleAlert, info: Info, ok: CircleCheck, down: Download, plus: Plus, lock: Lock, clip: Paperclip,
  doc: FileText, edit: FilePen, home: House, grid: LayoutGrid, store: Store, briefcase: Briefcase, wallet: Wallet, inbox: Inbox, message: MessageCircle,
  bell: Bell, settings: Settings, help: CircleHelp, user: User, users: Users, building: Building2, offers: HandCoins, bank: Landmark,
  calendar: CalendarClock, transfer: ArrowLeftRight, shield: ShieldCheck, shieldplain: Shield, review: ClipboardCheck, history: History,
  verified: BadgeCheck, scale: Scale, book: BookOpen, receipt: Receipt, savings: PiggyBank, sheet: FileSpreadsheet, trend: TrendingUp,
  megaphone: Megaphone, target: Target, activity: Activity, key: KeyRound, log: ScrollText, server: Server, sliders: SlidersHorizontal,
  package: Package, chart: ChartColumn, logout: LogOut, sun: Sun, moon: Moon, system: Monitor, gauge: Gauge, cash: Banknote, usercog: UserCog,
  up: ArrowUp, arrowdown: ArrowDown, database: Database, eye: Eye, eyeoff: EyeOff, globe: Globe, image: Image, trash: Trash2,
}

const props = withDefaults(defineProps<{ n: string; size?: number; stroke?: number }>(), { size: 18, stroke: 1.5 })
const C = computed(() => MAP[props.n] ?? Info)
</script>

<template>
  <component :is="C" :size="size" :stroke-width="stroke" aria-hidden="true" class="shrink-0" />
</template>
