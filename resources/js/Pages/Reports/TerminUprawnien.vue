<template>
  <div>
    <RaportMenu />
    <Head title="Termin uprawnień" />
    <h1 class="mb-6 text-3xl font-bold">Termin uprawnień</h1>

    <!-- Zakładki -->
    <div class="flex gap-2 mb-6 border-b border-gray-200">
      <button
        type="button"
        class="px-4 py-2 -mb-px border-b-2 font-medium text-sm"
        :class="tab === 'koncze' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
        @click="tab = 'koncze'"
      >
        Kończące się terminy
      </button>
      <button
        type="button"
        class="px-4 py-2 -mb-px border-b-2 font-medium text-sm flex items-center gap-2"
        :class="tab === 'braki' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
        @click="tab = 'braki'"
      >
        Brak dokumentów
        <span v-if="braki.length" class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-800">{{ braki.length }}</span>
      </button>
    </div>

    <!-- TAB: Kończące się terminy -->
    <div v-show="tab === 'koncze'">
      <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <label class="text-sm text-gray-600">Okno:</label>
          <select v-model="form.days" class="form-select text-sm py-1.5" @change="reload">
            <option value="30">Najbliższe 30 dni</option>
            <option value="60">Najbliższe 60 dni</option>
            <option value="90">Najbliższe 90 dni</option>
            <option value="all">Wszystkie ważne</option>
          </select>

          <div class="flex flex-wrap gap-1">
            <button
              v-for="c in categories"
              :key="c"
              type="button"
              class="px-2.5 py-1 rounded-full text-xs font-medium border"
              :class="activeCategory === c ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
              @click="activeCategory = c"
            >
              {{ c }}
            </button>
          </div>
        </div>
        <search-filter-no-filtr v-model="form.search" class="w-full lg:max-w-xs" @reset="resetSearch" />
      </div>

      <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="w-full whitespace-nowrap text-sm">
          <thead>
            <tr class="text-left font-bold border-b bg-gray-50">
              <th class="py-4 px-6">Pracownik</th>
              <th class="py-4 px-6">Kategoria</th>
              <th class="py-4 px-6">Nazwa / typ</th>
              <th class="py-4 px-6">Koniec</th>
              <th class="py-4 px-6">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in displayed" :key="index" class="hover:bg-gray-50">
              <td class="border-t px-6 py-3">
                <Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${item.client_id}/edit`">
                  {{ item.last_name }} {{ item.first_name }}
                </Link>
              </td>
              <td class="border-t px-6 py-3">
                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">{{ item.category }}</span>
              </td>
              <td class="border-t px-6 py-3 text-gray-700">{{ item.name }}</td>
              <td class="border-t px-6 py-3 font-semibold" :class="endClass(item.end)">{{ item.end }}</td>
              <td class="border-t px-6 py-3">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="statusBadge(item.end).class"
                >
                  {{ statusBadge(item.end).label }}
                </span>
              </td>
            </tr>
            <tr v-if="displayed.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                Brak terminów w wybranym oknie i kategorii.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="mt-3 text-xs text-gray-400">
        Pokazujemy terminy przeterminowane (do 7 dni wstecz) oraz kończące się w wybranym oknie. Kliknij pracownika, aby przejść do profilu.
      </p>
    </div>

    <!-- TAB: Brak dokumentów -->
    <div v-show="tab === 'braki'">
      <p class="mb-4 text-sm text-gray-500">
        Pracownicy aktualnie lub w przyszłości przypisani do budowy, którym brakuje <span class="font-medium">ważnego</span> dokumentu (badania, A1, uprawnienia, BHP).
      </p>
      <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="w-full whitespace-nowrap text-sm">
          <thead>
            <tr class="text-left font-bold border-b bg-gray-50">
              <th class="py-4 px-6">Pracownik</th>
              <th class="py-4 px-6">Brakujące dokumenty</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in braki" :key="p.id" class="hover:bg-gray-50">
              <td class="border-t px-6 py-3">
                <Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${p.id}/edit`">{{ p.name }}</Link>
              </td>
              <td class="border-t px-6 py-3">
                <span v-for="m in p.missing" :key="m" class="inline-block mr-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">{{ m }}</span>
              </td>
            </tr>
            <tr v-if="braki.length === 0">
              <td colspan="2" class="px-6 py-10 text-center text-gray-400">
                Wszyscy przypisani pracownicy mają komplet ważnych dokumentów.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import RaportMenu from '@/Shared/RaportMenu'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'
import throttle from 'lodash/throttle'

export default {
  components: {
    Head,
    Link,
    RaportMenu,
    SearchFilterNoFiltr,
  },
  layout: Layout,
  props: {
    data: { type: Array, default: () => [] },
    braki: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      tab: 'koncze',
      activeCategory: 'Wszystkie',
      categories: ['Wszystkie', 'BHP', 'A1', 'Badania lekarskie', 'Uprawnienia', 'PBIOZ'],
      form: {
        search: this.filters.search,
        days: this.filters.days || '30',
      },
    }
  },
  computed: {
    displayed() {
      if (this.activeCategory === 'Wszystkie') return this.data
      return this.data.filter((item) => item.category === this.activeCategory)
    },
  },
  watch: {
    'form.search': throttle(function () {
      this.reload()
    }, 300),
  },
  methods: {
    reload() {
      this.$inertia.get('/reports/koniecUprawinien',
        { search: this.form.search || undefined, days: this.form.days },
        { preserveState: true, preserveScroll: true, replace: true })
    },
    resetSearch() {
      this.form.search = null
    },
    daysLeft(end) {
      if (!end) return null
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const d = new Date(end)
      d.setHours(0, 0, 0, 0)
      return Math.round((d - today) / 86400000)
    },
    endClass(end) {
      const n = this.daysLeft(end)
      if (n === null) return 'text-gray-700'
      if (n < 0) return 'text-red-600'
      if (n <= 30) return 'text-orange-600'
      return 'text-gray-800'
    },
    statusBadge(end) {
      const n = this.daysLeft(end)
      if (n === null) return { label: '—', class: 'bg-gray-100 text-gray-600' }
      if (n < 0) return { label: `po terminie (${-n} dni)`, class: 'bg-red-100 text-red-800' }
      if (n === 0) return { label: 'kończy się dziś', class: 'bg-red-100 text-red-800' }
      if (n <= 30) return { label: `🔔 za ${n} dni`, class: 'bg-orange-100 text-orange-800' }
      return { label: `za ${n} dni`, class: 'bg-green-100 text-green-800' }
    },
  },
}
</script>
