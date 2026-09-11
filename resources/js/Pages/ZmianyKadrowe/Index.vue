<template>
  <div>
    <Head title="Zmiany kadrowe" />
    <h1 class="mb-2 text-3xl font-bold text-gray-900">Zmiany kadrowe</h1>
    <p class="mb-6 text-sm text-gray-500">
      Zmiany pobytów na budowach do przygotowania aneksów.
      Nieobsłużonych: <span class="font-bold text-gray-700">{{ licznik }}</span>.
    </p>

    <div class="flex items-center gap-3 mb-6">
      <div class="flex bg-white rounded shadow overflow-hidden">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium"
          :class="filters.pokaz !== 'wszystkie' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-50'"
          @click="pokaz('nieobsluzone')"
        >
          Do obsłużenia
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium"
          :class="filters.pokaz === 'wszystkie' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-50'"
          @click="pokaz('wszystkie')"
        >
          Wszystkie
        </button>
      </div>
    </div>

    <p v-if="paczki.length === 0" class="p-8 text-center text-sm text-gray-400 italic bg-white rounded-md shadow">
      Nic do obsłużenia.
    </p>

    <div v-for="paczka in paczki" :key="paczka.paczka" class="mb-4 bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-gray-50 border-b border-gray-100">
        <div>
          <div class="font-semibold text-gray-800">{{ paczka.naglowek }}</div>
          <div class="text-xs text-gray-500">
            zgłosił {{ paczka.autor }}, {{ paczka.kiedy }}
            <span v-if="paczka.nieobsluzonych > 0" class="ml-2 text-yellow-700">
              — {{ paczka.nieobsluzonych }} do obsłużenia
            </span>
            <span v-else class="ml-2 text-green-700">— obsłużone</span>
          </div>
        </div>
        <button
          v-if="paczka.nieobsluzonych > 0"
          type="button"
          class="btn-indigo text-sm"
          @click="zamknij({ paczka: paczka.paczka }, `Oznaczyć całą paczkę (${paczka.osob}) jako obsłużoną?`)"
        >
          Umowy gotowe — cała paczka
        </button>
      </div>

      <table class="w-full text-sm">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Pracownik</th>
            <th>Zmiana</th>
            <th>Termin</th>
            <th>Status</th>
            <th />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="zmiana in paczka.zmiany" :key="zmiana.id" class="hover:bg-gray-50">
            <td class="px-6 py-3">
              <Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${zmiana.contact_id}/edit`">
                {{ zmiana.pracownik }}
              </Link>
              <!-- Zwolniony trafia do archiwum — nazwisko zostaje, żeby wiadomo
                   było, kogo wpis dotyczył. -->
              <span v-if="zmiana.pracownik_w_archiwum" class="block text-[10px] text-gray-400">
                w archiwum
              </span>
            </td>
            <td class="px-6 py-3 text-gray-700">
              {{ zmiana.typ_label }}
              <span v-if="zmiana.budowa_z && zmiana.budowa_do && zmiana.budowa_z !== zmiana.budowa_do" class="block text-xs text-gray-500">
                {{ zmiana.budowa_z }} → {{ zmiana.budowa_do }}
              </span>
              <span v-else-if="zmiana.budowa_do" class="block text-xs text-gray-500">{{ zmiana.budowa_do }}</span>
              <span v-else-if="zmiana.budowa_z" class="block text-xs text-gray-500">{{ zmiana.budowa_z }}</span>
            </td>
            <td class="px-6 py-3 text-gray-700 whitespace-nowrap">
              <span v-if="zmiana.stary_termin" class="block text-xs text-gray-400 line-through">{{ zmiana.stary_termin }}</span>
              <span v-if="zmiana.nowy_termin">{{ zmiana.nowy_termin }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-6 py-3">
              <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full border" :class="statusClass(zmiana.status)">
                {{ zmiana.status_label }}
              </span>
              <span v-if="zmiana.obsluzyl" class="block mt-1 text-[10px] text-gray-400">
                {{ zmiana.obsluzyl }}, {{ zmiana.obsluzono }}
              </span>
            </td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
              <!-- Aneks z wypełnioną budową i terminem tej zmiany. -->
              <a
                v-if="zmiana.link_aneks"
                :href="zmiana.link_aneks"
                class="text-xs text-gray-700 hover:underline mr-3"
              >
                Generuj aneks
              </a>
              <button
                v-if="zmiana.status === 'nowa'"
                type="button"
                class="text-xs text-indigo-600 hover:underline mr-3"
                @click="zmienStatus({ id: zmiana.id, status: 'w_przygotowaniu' })"
              >
                Biorę
              </button>
              <button
                v-if="!zamknieta(zmiana.status)"
                type="button"
                class="text-xs text-green-700 hover:underline mr-3"
                @click="zmienStatus({ id: zmiana.id, status: 'gotowa' })"
              >
                Umowa gotowa
              </button>
              <!-- Zjazd z budowy bez kolejnej roboty: nie ma czego aneksować,
                   a sprawa i tak musi zejść z listy kadr. -->
              <button
                v-if="!zamknieta(zmiana.status)"
                type="button"
                class="text-xs text-gray-600 hover:underline"
                @click="zamknijBezAneksu(zmiana)"
              >
                Bez aneksu
              </button>
              <button
                v-if="zamknieta(zmiana.status)"
                type="button"
                class="text-xs text-gray-400 hover:underline"
                @click="zmienStatus({ id: zmiana.id, status: 'nowa' })"
              >
                Cofnij
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
    Link,
  },
  layout: Layout,
  props: {
    paczki: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    licznik: { type: Number, default: 0 },
  },
  methods: {
    pokaz(co) {
      this.$inertia.get('/zmiany-kadrowe', { pokaz: co }, { preserveState: true, replace: true })
    },
    zmienStatus(dane) {
      this.$inertia.put('/zmiany-kadrowe', dane, { preserveScroll: true })
    },
    zamknieta(status) {
      return status === 'gotowa' || status === 'bez_aneksu'
    },
    zamknijBezAneksu(zmiana) {
      const pytanie = `Zamknąć bez aneksu: ${zmiana.pracownik} — ${zmiana.typ_label}?\n\n`
        + 'Użyj tego, gdy do zmiany nie trzeba żadnego dokumentu (np. pracownik zjechał z budowy). '
        + 'Wpis zostaje w rejestrze, tylko przestaje czekać na kadry.'

      if (confirm(pytanie)) {
        this.zmienStatus({ id: zmiana.id, status: 'bez_aneksu' })
      }
    },
    zamknij(dane, pytanie) {
      if (confirm(pytanie)) {
        this.zmienStatus({ ...dane, status: 'gotowa' })
      }
    },
    statusClass(status) {
      return {
        nowa: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        w_przygotowaniu: 'bg-blue-100 text-blue-800 border-blue-200',
        gotowa: 'bg-green-100 text-green-800 border-green-200',
        bez_aneksu: 'bg-gray-100 text-gray-700 border-gray-300',
      }[status] || 'bg-gray-100 text-gray-800 border-gray-200'
    },
  },
}
</script>
