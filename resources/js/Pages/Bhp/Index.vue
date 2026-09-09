<template>
  <div>
    <Head title="Szkolenia BHP" />
    <WorkerMenu :contactId="contactId" :userOwner="userOwner" />

    <pracownik-naglowek :pracownik="pracownik" tytul="Szkolenia BHP" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="checkbox" class="form-checkbox" :checked="pokazKosz" @change="przelaczKosz" />
        <span>Pokaż usunięte</span>
      </label>
      <Link v-if="!kierownik" class="btn-indigo" :href="`/contacts/${contactId}/bhp/create`">
        <span>Dodaj</span>
      </Link>
    </div>

    <div class="hidden sm:block bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Rodzaj szkolenia</th>
            <th>Od</th>
            <th>Do</th>
            <th>Stan</th>
            <th />
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
              <Link v-if="!kierownik" class="text-indigo-600 hover:underline" :href="`/contacts/${contactId}/bhp/${item.id}/edit`">
                {{ item.bhp ? item.bhp.name : '—' }}
              </Link>
              <span v-else>{{ item.bhp ? item.bhp.name : '—' }}</span>
              <span v-if="item.deleted_at" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">w koszu</span>
              <!-- Skan wgrany przy tym wpisie — od razu widać, czy jest. -->
              <a
                v-if="item.skan"
                target="_blank"
                :href="`/contacts/${contactId}/documents/${item.skan}`"
                class="ml-2 text-xs text-indigo-600 hover:underline"
              >skan</a>
            </td>
            <td class="border-t px-6 py-4 tabular-nums">{{ item.start || '—' }}</td>
            <td class="border-t px-6 py-4 tabular-nums font-medium" :class="klasaTerminu(item)">{{ item.end || '—' }}</td>
            <td class="border-t px-6 py-4 text-sm" :class="klasaTerminu(item)">{{ opisTerminu(item) }}</td>
            <td class="border-t px-6 py-4 text-right">
              <!-- Dotad jedynym wejsciem w edycje byla nazwa wpisu, ktora nie wygladala na odnosnik. -->
              <Link
                v-if="!kierownik && !item.deleted_at"
                class="mr-4 text-sm text-indigo-600 hover:text-indigo-800"
                :href="`/contacts/${contactId}/bhp/${item.id}/edit`"
              >
                Edytuj
              </Link>
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

    <!-- Telefon: karty zamiast przewijanej w bok tabeli. -->
    <div class="sm:hidden space-y-3">
      <div
        v-for="item in wiersze"
        :key="item.id"
        class="bg-white rounded-md shadow p-4"
        :class="item.deleted_at ? 'text-gray-400' : ''"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="font-medium break-words">{{ item.bhp ? item.bhp.name : '—' }}</div>
          <span v-if="item.deleted_at" class="flex-shrink-0 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">w koszu</span>
          <a
            v-if="item.skan"
            target="_blank"
            :href="`/contacts/${contactId}/documents/${item.skan}`"
            class="flex-shrink-0 text-xs text-indigo-600"
          >skan</a>
        </div>
        <div class="mt-1 text-sm text-gray-500 tabular-nums">{{ item.start || '—' }} → {{ item.end || '—' }}</div>
        <div class="mt-1 text-sm" :class="klasaTerminu(item)">{{ opisTerminu(item) }}</div>
        <div v-if="!kierownik" class="mt-3 flex items-center gap-4">
          <Link class="text-sm text-indigo-600" :href="`/contacts/${contactId}/bhp/${item.id}/edit`">Edytuj</Link>
          <button v-if="item.deleted_at" type="button" class="text-sm text-indigo-600" @click="przywroc(item)">Przywróć</button>
          <button v-else type="button" class="text-sm text-red-600" @click="usun(item)">Usuń</button>
        </div>
      </div>
      <p v-if="wiersze.length === 0" class="bg-white rounded-md shadow p-4 text-sm text-gray-400">
        Brak wpisów dla tego pracownika.
      </p>
    </div>
    <pagination v-if="bhps.links" class="mt-4" :links="bhps.links" />

    <SkanyDokumentow
      :contact-id="contactId"
      :documents="documents"
      :kierownik="kierownik"
      tytul="Skany szkoleń BHP"
      trasa-usuwania="bhp"
    />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import { prowadziBudowy } from '@/role'
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
    pracownik: { type: Object, default: null },
    contact: Object,
    bhps: Object,
    documents: Object,
    userOwner: Number,
  },
  computed: {
    contactId() {
      return this.contact.id
    },
    kierownik() {
      return prowadziBudowy(this.userOwner)
    },
    pokazKosz() {
      return this.filters.trashed === 'with'
    },
    wiersze() {
      return this.bhps.data || this.bhps
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
        `/contacts/${this.contactId}/bhp`,
        zdarzenie.target.checked ? { trashed: 'with' } : {},
        { preserveScroll: true, replace: true }
      )
    },
    usun(item) {
      if (confirm('Przenieść ten wpis do kosza?')) {
        this.$inertia.delete(`/bhp/${item.id}`, { preserveScroll: true })
      }
    },
    przywroc(item) {
      this.$inertia.put(`/bhp/${item.id}/restore`, {}, { preserveScroll: true })
    },
  },
}
</script>
