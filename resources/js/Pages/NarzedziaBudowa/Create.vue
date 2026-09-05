<template>
  <div>
    <BudMenu :budId="organization.id" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{ organization.name }}</Link>
      <span class="text-indigo-400 font-medium">/</span>
      <p class="text-base">Sprzęt na budowie</p>
    </h1>

    <!-- Pasek wydania — pojawia się dopiero, gdy coś jest zaznaczone. -->
    <div v-if="zaznaczone.length" class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-md">
      <div class="flex flex-wrap items-end gap-4">
        <div class="font-semibold text-indigo-900">
          Zaznaczono {{ zaznaczone.length }} {{ zaznaczone.length === 1 ? 'sztukę' : 'szt.' }}
        </div>
        <div>
          <label class="block text-xs text-gray-600">Od</label>
          <input v-model="wydanie.start" type="date" class="form-input mt-1 w-40" />
        </div>
        <div>
          <label class="block text-xs text-gray-600">Do (można zostawić puste)</label>
          <input v-model="wydanie.end" type="date" class="form-input mt-1 w-40" />
        </div>
        <button class="btn-indigo" type="button" :disabled="!wydanie.start" @click="wydaj">Wydaj na budowę</button>
        <button class="text-gray-500 hover:text-gray-800 underline" type="button" @click="zaznaczone = []">Odznacz</button>
      </div>
      <div v-if="ostrzezenieBadan" class="mt-2 text-sm text-red-600">
        Uwaga: wśród zaznaczonych jest sprzęt z nieaktualnymi badaniami technicznymi.
      </div>
      <div v-if="bledy" class="mt-2 text-sm text-red-600">{{ bledy }}</div>
    </div>

    <div class="mb-8 bg-white rounded-md shadow overflow-hidden">
      <h3 class="font-medium text-xl px-6 py-4 border-b border-gray-100">Sprzęt na tej budowie</h3>
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left text-xs uppercase tracking-wider text-gray-500 bg-gray-50">
            <th class="py-3 px-6">Nazwa</th>
            <th class="py-3 px-6">Numer seryjny</th>
            <th class="py-3 px-6">Badania techniczne</th>
            <th class="py-3 px-6">Termin</th>
            <th class="py-3 px-6 text-right">Akcje</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in naBudowie" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-3">
              <span v-if="item.nazwa" class="font-medium text-gray-900">{{ item.nazwa }}</span>
              <span v-else class="text-red-500 italic">Sprzęt usunięty z bazy (wpis #{{ item.narzedzia_id }})</span>
            </td>
            <td class="px-6 py-3 text-gray-600">{{ item.numer_seryjny || '—' }}</td>
            <td class="px-6 py-3">
              <span :class="klasaBadan(item.badania_status)">{{ item.waznosc_badan || 'brak daty' }}</span>
            </td>
            <td class="px-6 py-3 text-gray-600">
              {{ item.od || '—' }}<span v-if="item.do"> – {{ item.do }}</span>
              <span v-if="item.zakonczony" class="ml-2 text-xs text-gray-400">(zakończony)</span>
            </td>
            <td class="px-6 py-3 text-right">
              <delete-button
                :href="`/budowy/${organization.id}/narzedzia/${item.id}/destroy`"
                confirm="Zdjąć ten sprzęt z budowy?"
              />
            </td>
          </tr>
          <tr v-if="naBudowie.length === 0">
            <td class="px-6 py-6 text-center text-gray-500" colspan="5">Brak sprzętu na tej budowie</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="bg-white rounded-md shadow overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="font-medium text-xl">Sprzęt dostępny w magazynie</h3>
        <input v-model="szukaj" type="text" placeholder="Szukaj po nazwie lub numerze…" class="form-input text-sm py-1.5 w-72" />
      </div>
      <table class="w-full">
        <thead>
          <tr class="text-left text-xs uppercase tracking-wider text-gray-500 bg-gray-50">
            <th class="py-3 px-6">Sprzęt</th>
            <th class="py-3 px-6 text-center">Wolnych sztuk</th>
            <th class="py-3 px-6">Badania</th>
            <th class="py-3 px-6" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="grupa in widoczneGrupy" :key="grupa.klucz">
            <tr class="hover:bg-gray-50 cursor-pointer" @click="przelacz(grupa.klucz)">
              <td class="px-6 py-3 font-medium text-gray-900">
                {{ grupa.nazwa }}
                <span v-if="grupa.ma_modele" class="ml-2 text-xs text-gray-400">{{ grupa.modele.length }} modele</span>
              </td>
              <td class="px-6 py-3 text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                  {{ grupa.dostepne }}
                </span>
              </td>
              <td class="px-6 py-3">
                <span v-if="grupa.badania_uwaga > 0" class="text-xs text-red-700">{{ grupa.badania_uwaga }} do sprawdzenia</span>
                <span v-else class="text-gray-300 text-xs">-</span>
              </td>
              <td class="px-6 py-3 text-right text-xs text-gray-400">
                {{ rozwiniete.includes(grupa.klucz) ? 'zwiń' : 'pokaż sztuki' }}
              </td>
            </tr>

            <template v-if="rozwiniete.includes(grupa.klucz)">
              <template v-for="model in grupa.modele" :key="model.klucz">
                <!-- Wiersz modelu i wiersze sztuk trzymamy w tej samej tabeli,
                     żeby daty badań stały pod nagłówkiem "Badania". -->
                <tr v-if="grupa.ma_modele" class="bg-gray-50">
                  <td class="pl-12 pr-6 py-2 text-sm font-medium text-gray-700">{{ model.nazwa }}</td>
                  <td class="px-6 py-2 text-center text-sm text-gray-500">
                    {{ model.dostepne }} wolnych
                    <button type="button" class="ml-2 text-xs text-indigo-600 hover:underline" @click="zaznaczModel(model)">
                      {{ wszystkieZaznaczone(model) ? 'odznacz' : 'zaznacz' }}
                    </button>
                  </td>
                  <td class="px-6 py-2" />
                  <td class="px-6 py-2" />
                </tr>

                <tr v-for="sztuka in model.sztuki" :key="sztuka.id" class="hover:bg-gray-50">
                  <td class="pl-12 pr-6 py-2">
                    <label class="flex items-center cursor-pointer">
                      <input v-model="zaznaczone" type="checkbox" :value="sztuka.id" class="mr-3" />
                      <span class="font-medium text-gray-800">{{ sztuka.numer_seryjny || '—' }}</span>
                    </label>
                  </td>
                  <td class="px-6 py-2" />
                  <td class="px-6 py-2 text-sm">
                    <span :class="klasaBadan(sztuka.badania_status)">{{ sztuka.waznosc_badan || 'brak daty' }}</span>
                  </td>
                  <td class="px-6 py-2 text-right">
                    <Link :href="`/narzedzia/${sztuka.id}/edit`" class="text-indigo-600 hover:underline text-sm">Karta sprzętu</Link>
                  </td>
                </tr>
              </template>
            </template>
          </template>

          <tr v-if="widoczneGrupy.length === 0">
            <td class="px-6 py-6 text-center text-gray-500" colspan="4">Brak wolnego sprzętu w magazynie</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu'
import DeleteButton from '@/Shared/DeleteButton.vue'

export default {
  components: {
    Link,
    BudMenu,
    DeleteButton,
  },
  layout: Layout,
  props: {
    organization: Object,
    grupy: Array,
    naBudowie: Array,
  },
  data() {
    return {
      rozwiniete: [],
      zaznaczone: [],
      szukaj: '',
      bledy: null,
      wydanie: {
        start: new Date().toISOString().slice(0, 10),
        end: null,
      },
    }
  },
  computed: {
    // Szukanie schodzi do sztuk, żeby dało się trafić po numerze seryjnym.
    widoczneGrupy() {
      if (!this.szukaj) return this.grupy
      const fraza = this.szukaj.toLowerCase()

      return this.grupy
        .map((grupa) => {
          const modele = grupa.modele
            .map((model) => {
              if (model.nazwa.toLowerCase().includes(fraza) || grupa.nazwa.toLowerCase().includes(fraza)) return model
              const sztuki = model.sztuki.filter((s) => (s.numer_seryjny || '').toLowerCase().includes(fraza))
              return sztuki.length ? { ...model, sztuki, dostepne: sztuki.length } : null
            })
            .filter(Boolean)

          return modele.length ? { ...grupa, modele, dostepne: modele.reduce((n, m) => n + m.dostepne, 0) } : null
        })
        .filter(Boolean)
    },
    ostrzezenieBadan() {
      return this.grupy.some((g) =>
        g.modele.some((m) =>
          m.sztuki.some((s) => this.zaznaczone.includes(s.id) && ['po_terminie', 'wkrotce'].includes(s.badania_status))
        )
      )
    },
  },
  methods: {
    dostepneWModelu(model) {
      return model.sztuki.map((s) => s.id)
    },
    wszystkieZaznaczone(model) {
      const ids = this.dostepneWModelu(model)
      return ids.length > 0 && ids.every((id) => this.zaznaczone.includes(id))
    },
    zaznaczModel(model) {
      const ids = this.dostepneWModelu(model)
      if (this.wszystkieZaznaczone(model)) {
        this.zaznaczone = this.zaznaczone.filter((id) => !ids.includes(id))
      } else {
        this.zaznaczone = [...new Set([...this.zaznaczone, ...ids])]
      }
    },
    przelacz(klucz) {
      const i = this.rozwiniete.indexOf(klucz)
      if (i === -1) {
        this.rozwiniete.push(klucz)
      } else {
        this.rozwiniete.splice(i, 1)
      }
    },
    klasaBadan(status) {
      if (status === 'po_terminie') return 'text-red-700 font-semibold'
      if (status === 'wkrotce') return 'text-orange-700 font-semibold'
      if (status === 'brak') return 'text-gray-400'
      return 'text-gray-700'
    },
    wydaj() {
      this.bledy = null
      this.$inertia.post(
        `/budowy/${this.organization.id}/narzedzia`,
        {
          narzedzia_ids: this.zaznaczone,
          start: this.wydanie.start,
          end: this.wydanie.end,
        },
        {
          onSuccess: () => {
            this.zaznaczone = []
            this.wydanie.end = null
          },
          onError: (errors) => {
            this.bledy = Object.values(errors).join(' ')
          },
        }
      )
    },
  },
}
</script>
