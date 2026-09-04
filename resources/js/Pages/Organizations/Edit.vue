<template>
  <div>
    <Head :title="form.nazwaBud" />
    <BudMenu :budId="budId" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">Budowa</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.nazwaBud }}
    </h1>

    <!-- Podsumowanie budowy (rozbudowywalne — kolejne kafelki dojdą obok) -->
    <div class="mb-8 flex flex-wrap gap-4">
      <Link :href="`/budowy/${budId}/prognoza`" class="block bg-white rounded-md shadow px-5 py-4 hover:bg-gray-50 transition">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Kadra — szczyt zapotrzebowania</div>
        <template v-if="summary && summary.peak !== null">
          <div class="flex items-end gap-4">
            <div>
              <div class="text-2xl font-bold text-indigo-700">{{ summary.peak }}</div>
              <div class="text-xs text-gray-500">potrzebujemy (max)</div>
            </div>
            <div class="text-gray-300 text-2xl font-light">/</div>
            <div>
              <div class="text-2xl font-bold text-gray-800">{{ summary.assigned }}</div>
              <div class="text-xs text-gray-500">mamy przypisanych</div>
            </div>
            <span class="ml-2 self-center inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded" :class="gapClass">{{ gapLabel }}</span>
          </div>
          <div v-if="summary.peakStart" class="mt-1 text-xs text-gray-400">szczyt w tygodniu {{ summary.peakStart }} – {{ summary.peakEnd }}</div>
        </template>
        <div v-else class="text-sm text-gray-400">Brak wpisanej prognozy — dodaj w zakładce Prognoza.</div>
      </Link>
    </div>

    <trashed-message v-if="organization.deleted_at" class="mb-6" @restore="restore">Ta budowa jest usunięta</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.nazwaBud" :error="form.errors.nazwaBud" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Nazwa Projektu" />
          <text-input v-model="form.numerBud" :error="form.errors.numerBud" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Projektu" />
          <text-input v-model="form.city" :error="form.errors.city" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <client-picker v-model="form.name" v-model:clientId="form.crm_client_id" :error="form.errors.name" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Nazwa Klienta" />
          <select-input v-model="form.zaklad" :error="form.errors.zaklad" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Zakład podatkowy">
            <option :value="null">—</option>
            <option value="TAK">TAK</option>
            <option value="NIE">NIE</option>
          </select-input>
          <!-- Pracownicy ze stanowiskiem "Kierownik Projektu" (słownik /funkcja) -->
          <select-input
            v-model="form.kierownik_projektu_id"
            :error="form.errors.kierownik_projektu_id"
            :disabled="flag"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Kierownik projektu"
          >
            <option :value="null">—</option>
            <option v-for="osoba in kierownicyProjektow" :key="osoba.id" :value="osoba.id">{{ osoba.name }}</option>
          </select-input>
          <select-input v-model="form.country_id" :error="form.errors.country_id" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj Budowy">
            <option v-for="item in krajTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.addressBud" :error="form.errors.addressBud" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Adres Budowy" />
          <text-input v-model="form.addressKwat" :error="form.errors.addressKwat" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Adres Kwatery" />
        </div>

        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button
            v-if="!organization.deleted_at && (user_owner === 1 || user_owner === 2)"
            type="button"
            class="inline-flex items-center px-3 py-1 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors"
            @click="archive"
          >
            <icon name="trash" class="mr-1.5 w-3 h-3 fill-red-700" />
            Archiwizuj budowę
          </button>
          <loading-button v-if="!organization.deleted_at && (user_owner === 1 || user_owner === 2)" :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import ClientPicker from '@/Shared/ClientPicker'
import TrashedMessage from '@/Shared/TrashedMessage'
import BudMenu from '@/Shared/BudMenu'
import Icon from '@/Shared/Icon'

export default {
  components: {
    Head,
    Icon,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    BudMenu,
    ClientPicker,
  },
  layout: Layout,
  props: {
    organization: Object,
    krajTyps: Object,
    kierownicyProjektow: { type: Array, default: () => [] },
    kierownikBud: Object,
    user_owner: Number,
    flag: Boolean,
    inzyniers: Object,
    unfinishedWorkers: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
  },
  remember: 'form',
  data() {
    return {
      budId: this.organization.id,
      checkedValues: [],
      toggle: false,
      button: {
        text: 'Dodaj pracownika',
      },
      form: this.$inertia.form({
        name: this.organization.name,
        crm_client_id: this.organization.crm_client_id,
        nazwaBud: this.organization.nazwaBud,
        numerBud: this.organization.numerBud,
        city: this.organization.city,
        kierownikBud_id: this.organization.kierownikBud_id,
        zaklad: this.organization.zaklad,
        kierownik_projektu_id: this.organization.kierownik_projektu_id,
        country_id: this.organization.country_id,
        addressBud: this.organization.addressBud,
        addressKwat: this.organization.addressKwat,
        inzynier_id: this.organization.inzynier_id,
      }),
    }
  },
  computed: {
    gap() {
      if (!this.summary || this.summary.peak === null) return null
      return this.summary.assigned - this.summary.peak
    },
    gapLabel() {
      if (this.gap === null) return ''
      if (this.gap >= 0) return 'komplet'
      return `brakuje ${-this.gap}`
    },
    gapClass() {
      if (this.gap === null) return 'bg-gray-100 text-gray-700'
      return this.gap >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
    },
  },
  methods: {
    update() {
      this.form.put(`/budowy/${this.organization.id}`)
    },
    /**
     * Archiwizacja. Zakończone pobyty nie przeszkadzają — blokują tylko te trwające
     * i przyszłe. Admin może je pominąć, ale musi to świadomie potwierdzić.
     */
    archive() {
      const blokujacy = this.unfinishedWorkers

      if (blokujacy.length === 0) {
        if (confirm('Zarchiwizować budowę? Historia pracowników i godzin zostaje.')) {
          this.$inertia.delete(`/budowy/${this.organization.id}`)
        }
        return
      }

      const lista = blokujacy
        .slice(0, 5)
        .map((worker) => `• ${worker.name}${worker.end ? ` — do ${worker.end}` : ' — bez daty końca'}`)
        .join('\n')
      const reszta = blokujacy.length > 5 ? `\n• i ${blokujacy.length - 5} innych` : ''

      if (this.user_owner !== 1) {
        alert(`Na budowie pracuje jeszcze ${blokujacy.length} os\u00f3b:\n${lista}${reszta}\n\nPopraw daty pobytu albo poczekaj do ich zakończenia.`)
        return
      }

      if (confirm(`Na budowie pracuje jeszcze ${blokujacy.length} os\u00f3b:\n${lista}${reszta}\n\nZarchiwizować mimo to?`)) {
        this.$inertia.delete(`/budowy/${this.organization.id}`, { data: { force: true } })
      }
    },
    restore() {
      if (confirm('Jesteś pewnien, że chcesz przywrócić budowę?')) {
        this.$inertia.put(`/budowy/${this.organization.id}/restore`)
      }
    },
    toggleSeen: function () {
      this.toggle = !this.toggle
      this.button.text = this.toggle ? 'Zamknij' : 'Dodaj pracownika'
    },
    created() {
      this.$inertia.post(`/contacts/${this.organization.id}/addPracownik`, this.checkedValues)
    },
  },
}
</script>
