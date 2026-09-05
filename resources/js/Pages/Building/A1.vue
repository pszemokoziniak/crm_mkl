<template>
  <Head title="A1" />
  <BudMenu :bud-id="build" />
  <budowa-naglowek :bud-id="buildDetails.id" :nazwa="buildDetails.nazwaBud" tytul="A1" />
  <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 mb-6">
    <h1 class="text-3xl font-bold">A1</h1>
    <span v-if="orgCountry" class="text-gray-500">kraj budowy: <span class="font-semibold text-gray-700">{{ orgCountry }}</span></span>
    <span v-else class="text-orange-700 text-sm font-semibold">budowa nie ma przypisanego kraju — nie można zweryfikować A1</span>
  </div>

  <!-- Podsumowanie pokrycia -->
  <div v-if="summary.total" class="flex flex-wrap gap-3 mb-6">
    <div class="px-4 py-3 rounded-lg border bg-white shadow-sm">
      <div class="text-2xl font-bold">{{ summary.total }}</div>
      <div class="text-xs text-gray-500">pobytów na budowie</div>
    </div>
    <div class="px-4 py-3 rounded-lg border border-green-200 bg-green-50">
      <div class="text-2xl font-bold text-green-800">{{ summary.ok }}</div>
      <div class="text-xs text-green-700">A1 potwierdzone</div>
    </div>
    <div class="px-4 py-3 rounded-lg border border-orange-200 bg-orange-50">
      <div class="text-2xl font-bold text-orange-800">{{ summary.do_weryfikacji }}</div>
      <div class="text-xs text-orange-700">do weryfikacji (A1 bez kraju)</div>
    </div>
    <div class="px-4 py-3 rounded-lg border border-red-200 bg-red-50">
      <div class="text-2xl font-bold text-red-800">{{ summary.braki }}</div>
      <div class="text-xs text-red-700">bez ważnego A1</div>
    </div>
  </div>

  <div class="flex items-center justify-between mb-6">
    <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
    </search-filter-no-filtr>
  </div>

  <div class="bg-white rounded-md shadow overflow-x-auto">
    <table class="w-full whitespace-nowrap">
      <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
        <th class="pb-4 pt-6 px-6">Pobyt na budowie</th>
        <th class="pb-4 pt-6 px-6">A1 (od–do, kraj)</th>
        <th class="pb-4 pt-6 px-6">Status A1</th>
        <th class="pb-4 pt-6 px-6" />
      </tr>
      <tr
        v-for="row in rows"
        :key="row.id"
        class="hover:bg-gray-100 focus-within:bg-gray-100"
        :class="{ 'opacity-60': row.period === 'zakonczony' }"
      >
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500 font-medium" :href="`/contacts/${row.contact_id}/edit`">
            {{ row.last_name }} {{ row.first_name }}
          </Link>
        </td>
        <td class="border-t">
          <div class="px-6 py-4">
            <div>{{ row.start || '—' }} → {{ row.end || '—' }}</div>
            <span
              class="inline-flex items-center mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full"
              :class="periodBadge(row.period).class"
            >
              {{ periodBadge(row.period).label }}
            </span>
          </div>
        </td>
        <td class="border-t">
          <div class="px-6 py-4">
            <template v-if="row.a1">
              <div>{{ row.a1.start || '—' }} → {{ row.a1.end || '—' }}</div>
              <div v-if="row.a1.kraj" class="text-sm text-gray-600">{{ row.a1.kraj }}</div>
              <div v-else class="text-sm text-orange-700 font-medium">— brak wpisanego kraju —</div>
            </template>
            <span v-else class="text-gray-400">brak dokumentu</span>
          </div>
        </td>
        <td class="border-t">
          <div class="px-6 py-4">
            <span
              class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded"
              :class="statusBadge(row.status).class"
            >
              {{ statusBadge(row.status).label }}
            </span>
          </div>
        </td>
        <td class="w-px border-t">
          <Link
            class="flex items-center px-4 text-indigo-500 hover:text-indigo-700 text-sm font-medium"
            :href="`/contacts/${row.contact_id}/a1`"
          >
            Zarządzaj A1
          </Link>
        </td>
      </tr>
      <tr v-if="rows.length === 0">
        <td class="px-6 py-4 border-t" colspan="5">Nie znaleziono pobytów na tej budowie</td>
      </tr>
    </table>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'
import mapValues from 'lodash/mapValues'

export default {
  components: {
    BudowaNaglowek,
    Head,
    Link,
    BudMenu,
    SearchFilterNoFiltr,
  },
  layout: Layout,
  props: {
    build: Number,
    buildDetails: Object,
    orgCountry: String,
    rows: Array,
    summary: Object,
    filters: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get(`/budowy/${this.build}/a1`, pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    statusBadge(status) {
      return {
        ok: { label: 'A1 potwierdzone', class: 'bg-green-100 text-green-800' },
        brak_kraju: { label: 'A1 bez kraju — do weryfikacji', class: 'bg-orange-100 text-orange-800' },
        czesciowe: { label: 'A1 nie pokrywa całego pobytu', class: 'bg-orange-100 text-orange-800' },
        zly_kraj: { label: 'A1 na inny kraj', class: 'bg-red-100 text-red-800' },
        wygasle: { label: 'brak A1 na ten termin', class: 'bg-red-100 text-red-800' },
        brak: { label: 'brak A1', class: 'bg-red-100 text-red-800' },
        brak_dat: { label: 'brak dat pobytu', class: 'bg-gray-100 text-gray-700' },
      }[status] || { label: status, class: 'bg-gray-100 text-gray-700' }
    },
    periodBadge(period) {
      return {
        trwa: { label: 'trwa / aktualny', class: 'bg-indigo-100 text-indigo-800' },
        przyszly: { label: 'przyszły', class: 'bg-indigo-50 text-indigo-700' },
        zakonczony: { label: 'zakończony', class: 'bg-gray-100 text-gray-600' },
      }[period] || { label: period, class: 'bg-gray-100 text-gray-600' }
    },
  },
}
</script>
