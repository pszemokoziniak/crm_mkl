<template>
  <div>
    <Head title="Rejestr logowań" />
    <h1 class="mb-2 text-3xl font-bold">
      Rejestr logowań
      <span class="text-xl font-medium text-gray-400">({{ logowania.total }} wpisów)</span>
    </h1>
    <p class="mb-6 text-sm text-gray-500">
      Kto, kiedy i skąd wchodził do systemu — razem z nieudanymi próbami.
      Wpisy starsze niż {{ miesiace_przechowywania }} miesięcy kasują się same.
      <span v-if="nieudane_7dni > 0" class="ml-1 font-semibold text-red-700">
        Nieudanych prób w ostatnich 7 dniach: {{ nieudane_7dni }}.
      </span>
    </p>

    <div class="flex flex-wrap items-end gap-3 mb-6">
      <div>
        <label class="block text-xs text-gray-600">Szukaj</label>
        <input v-model="form.szukaj" type="text" placeholder="nazwisko, e-mail albo IP" class="form-input mt-1 w-64" />
      </div>
      <div>
        <label class="block text-xs text-gray-600">Wynik</label>
        <select v-model="form.wynik" class="form-select mt-1 w-40">
          <option :value="null">wszystkie</option>
          <option value="udane">udane</option>
          <option value="nieudane">nieudane</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-600">Od</label>
        <input v-model="form.od" type="date" class="form-input mt-1 w-40" />
      </div>
      <div>
        <label class="block text-xs text-gray-600">Do</label>
        <input v-model="form.do" type="date" class="form-input mt-1 w-40" />
      </div>
      <button type="button" class="text-sm text-gray-500 hover:text-gray-800 underline" @click="wyczysc">Wyczyść</button>
    </div>

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs uppercase tracking-wider text-gray-500 bg-gray-50 border-b">
            <th class="py-4 px-6">Kiedy</th>
            <th class="py-4 px-6">Kto</th>
            <th class="py-4 px-6">Wynik</th>
            <th class="py-4 px-6">Adres IP</th>
            <th class="py-4 px-6">Przeglądarka</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="wpis in logowania.data" :key="wpis.id" class="hover:bg-gray-50">
            <td class="px-6 py-3 whitespace-nowrap tabular-nums text-gray-700">{{ wpis.kiedy }}</td>
            <td class="px-6 py-3">
              <Link v-if="wpis.user_id" :href="`/users/${wpis.user_id}/edit`" class="font-medium text-gray-900 hover:text-indigo-600">
                {{ wpis.kto || wpis.email }}
              </Link>
              <span v-else class="font-medium text-gray-900">{{ wpis.email || '—' }}</span>
              <span v-if="wpis.kto && wpis.email" class="block text-xs text-gray-400">{{ wpis.email }}</span>
            </td>
            <td class="px-6 py-3">
              <span
                class="inline-block px-2.5 py-0.5 text-xs font-medium border rounded-full"
                :class="wpis.udane
                  ? 'text-green-800 bg-green-100 border-green-200'
                  : 'text-red-800 bg-red-100 border-red-200'"
              >
                {{ wpis.udane ? 'wejście' : 'nieudana próba' }}
              </span>
              <span v-if="wpis.powod" class="block mt-0.5 text-xs text-gray-500">{{ wpis.powod }}</span>
            </td>
            <td class="px-6 py-3 tabular-nums text-gray-600">{{ wpis.ip || '—' }}</td>
            <td class="px-6 py-3 text-xs text-gray-400 max-w-md truncate" :title="wpis.przegladarka">
              {{ wpis.przegladarka || '—' }}
            </td>
          </tr>
          <tr v-if="logowania.data.length === 0">
            <td class="px-6 py-6 text-center text-gray-500" colspan="5">Brak wpisów dla tych warunków.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <pagination class="mt-6" :links="logowania.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import Pagination from '@/Shared/Pagination'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'

export default {
  components: { Head, Link, Pagination },
  layout: Layout,
  props: {
    filters: Object,
    logowania: Object,
    nieudane_7dni: { type: Number, default: 0 },
    miesiace_przechowywania: { type: Number, default: 12 },
  },
  data() {
    return {
      form: {
        szukaj: this.filters.szukaj,
        wynik: this.filters.wynik,
        od: this.filters.od,
        do: this.filters.do,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/logowania', pickBy(this.form), { preserveState: true, replace: true })
      }, 200),
    },
  },
  methods: {
    wyczysc() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
