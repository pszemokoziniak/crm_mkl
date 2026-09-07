<template>
  <div>
    <Head title="A1" />
    <WorkerMenu :contactId="contactId" :userOwner="userOwner" />

    <PracownikNaglowek :contact-id="contactId" :nazwa="pracownik" tytul="A1" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" class="form-checkbox" :checked="pokazKosz" @change="przelaczKosz" />
        <span>Pokaż usunięte</span>
      </label>
      <Link v-if="!kierownik" class="btn-indigo" :href="`/contacts/${contactId}/a1/create`">
        <span>Dodaj</span>
      </Link>
    </div>

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Kraj</th>
            <th class="pb-4 pt-6 px-6">Od</th>
            <th class="pb-4 pt-6 px-6">Do</th>
            <th class="pb-4 pt-6 px-6">Stan</th>
            <th class="pb-4 pt-6 px-6" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in wiersze"
            :key="item.id"
            class="hover:bg-gray-100 focus-within:bg-gray-100"
            :class="item.deleted_at ? 'text-gray-400' : ''"
          >
            <td class="border-t px-6 py-4">
              <!-- Kierownik ma tu podgląd, więc zamiast martwego href="" tekst. -->
              <Link v-if="!kierownik" class="focus:text-indigo-500" :href="`/contacts/${contactId}/a1/${item.id}/edit`">
                {{ item.kraj ? item.kraj.name : '—' }}
              </Link>
              <span v-else>{{ item.kraj ? item.kraj.name : '—' }}</span>
              <span v-if="item.deleted_at" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">w koszu</span>
            </td>
            <td class="border-t px-6 py-4 tabular-nums">{{ item.start || '—' }}</td>
            <td class="border-t px-6 py-4 tabular-nums font-medium" :class="klasaTerminu(item)">{{ item.end || '—' }}</td>
            <td class="border-t px-6 py-4 text-sm" :class="klasaTerminu(item)">{{ opisTerminu(item) }}</td>
            <td class="border-t px-6 py-4 text-right">
              <button
                v-if="!kierownik && item.deleted_at"
                type="button"
                class="text-sm text-indigo-600 hover:text-indigo-800"
                @click="przywroc(item)"
              >Przywróć</button>
              <button
                v-else-if="!kierownik"
                type="button"
                class="text-sm text-red-600 hover:text-red-800"
                @click="usun(item)"
              >Usuń</button>
            </td>
          </tr>
          <tr v-if="wiersze.length === 0">
            <td class="px-6 py-6 border-t text-gray-400" colspan="5">Brak wpisów dla tego pracownika.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination v-if="a1s.links" class="mt-4" :links="a1s.links" />

    <SkanyDokumentow
      :contact-id="contactId"
      :documents="documents"
      :kierownik="kierownik"
      tytul="Skany A1"
      trasa-usuwania="a1"
    />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import Pagination from '@/Shared/Pagination'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import SkanyDokumentow from '@/Shared/SkanyDokumentow'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: { Head, Link, Pagination, PracownikNaglowek, SkanyDokumentow, WorkerMenu },
  layout: Layout,
  props: {
    filters: { type: Object, default: () => ({}) },
    pracownik: { type: String, default: '' },
    contact: Object,
    a1s: Object,
    documents: Object,
    userOwner: Number,
  },
  computed: {
    contactId() {
      return this.contact.id
    },
    kierownik() {
      return this.userOwner === 3
    },
    pokazKosz() {
      return this.filters.trashed === 'with'
    },
    wiersze() {
      return this.a1s.data || this.a1s
    },
  },
  methods: {
    // Ta sama skala co na pulpicie i w badaniach.
    opisTerminu(item) {
      if (item.deleted_at) return 'w koszu'
      if (item.dni === null || item.dni === undefined) return 'bez daty końca'
      if (item.dni < 0) return `po terminie od ${Math.abs(item.dni)} dni`
      if (item.dni === 0) return 'kończy się dziś'
      return `zostało ${item.dni} dni`
    },
    klasaTerminu(item) {
      if (item.deleted_at) return 'text-gray-400'
      if (item.dni === null || item.dni === undefined) return 'text-gray-500'
      if (item.dni < 0) return 'text-red-700'
      return item.dni <= 30 ? 'text-orange-700' : 'text-gray-600'
    },
    przelaczKosz(zdarzenie) {
      this.$inertia.get(
        `/contacts/${this.contactId}/a1`,
        zdarzenie.target.checked ? { trashed: 'with' } : {},
        { preserveScroll: true, replace: true }
      )
    },
    usun(item) {
      if (confirm('Przenieść ten wpis do kosza?')) {
        this.$inertia.delete(`/a1/${item.id}`, { preserveScroll: true })
      }
    },
    przywroc(item) {
      this.$inertia.put(`/a1/${item.id}/restore`, {}, { preserveScroll: true })
    },
  },
}
</script>
