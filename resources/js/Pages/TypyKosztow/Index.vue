<template>
  <div>
    <Head title="Typy kosztów" />
    <h1 class="mb-2 text-2xl sm:text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/tools">Ustawienia</Link>
      <span class="text-indigo-400 font-medium">/</span> Typy kosztów
    </h1>
    <p class="mb-6 text-sm text-gray-500">
      Z tej listy wybiera się typ przy wpisywaniu kosztu. „Dzielony” = koszt budowy tego typu domyślnie rozkłada się
      na pracowników; „nocleg” = pokój z liczbą miejsc i zakwaterowanymi.
    </p>

    <div class="mb-6 bg-white rounded-md shadow-sm overflow-hidden">
      <form class="flex flex-wrap items-end gap-3 px-4 py-4 sm:px-6" @submit.prevent="dodaj">
        <div class="flex-1 min-w-56">
          <label class="form-label" for="nowy-typ">Nowy typ</label>
          <input id="nowy-typ" v-model="nowy.nazwa" type="text" class="form-input" placeholder="np. Parking, Prom" />
          <div v-if="nowy.errors.nazwa" class="form-error">{{ nowy.errors.nazwa }}</div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700 pb-2"><input v-model="nowy.dzielony" type="checkbox" class="form-checkbox" /> dzielony</label>
        <label class="flex items-center gap-2 text-sm text-gray-700 pb-2"><input v-model="nowy.nocleg" type="checkbox" class="form-checkbox" /> nocleg</label>
        <loading-button :loading="nowy.processing" class="btn-indigo" type="submit">Dodaj</loading-button>
      </form>
    </div>

    <div class="bg-white rounded-md shadow-sm divide-y divide-gray-100">
      <div v-for="t in typy" :key="t.id" class="px-4 py-3 sm:px-6">
        <div v-if="edytowany !== t.id" class="flex flex-wrap items-center gap-3">
          <span class="font-medium text-gray-900">{{ t.nazwa }}</span>
          <span v-if="t.dzielony" class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 text-indigo-800">dzielony</span>
          <span v-if="t.nocleg" class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">nocleg</span>
          <span class="text-xs text-gray-400">{{ t.uzyc }} użyć</span>
          <div class="ml-auto flex items-center gap-4 text-sm">
            <button type="button" class="text-indigo-600 hover:underline" @click="zacznij(t)">Edytuj</button>
            <button type="button" class="text-red-600 hover:underline" :disabled="t.uzyc > 0" :class="{ 'opacity-40 cursor-not-allowed': t.uzyc > 0 }" :title="t.uzyc > 0 ? 'Typ jest użyty na kosztach' : ''" @click="usun(t)">Usuń</button>
          </div>
        </div>
        <form v-else class="flex flex-wrap items-center gap-3" @submit.prevent="zapisz(t)">
          <input v-model="edycja.nazwa" type="text" class="form-input max-w-xs" />
          <label class="flex items-center gap-2 text-sm"><input v-model="edycja.dzielony" type="checkbox" class="form-checkbox" /> dzielony</label>
          <label class="flex items-center gap-2 text-sm"><input v-model="edycja.nocleg" type="checkbox" class="form-checkbox" /> nocleg</label>
          <loading-button :loading="edycja.processing" class="btn-indigo text-sm" type="submit">Zapisz</loading-button>
          <button type="button" class="text-sm text-gray-500" @click="edytowany = null">Anuluj</button>
          <div v-if="edycja.errors.nazwa" class="w-full form-error">{{ edycja.errors.nazwa }}</div>
        </form>
      </div>
      <p v-if="typy.length === 0" class="px-4 py-6 text-sm text-gray-500">Brak typów. Dodaj pierwszy powyżej.</p>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: { Head, Link, LoadingButton },
  layout: Layout,
  props: { typy: { type: Array, default: () => [] } },
  data() {
    return {
      edytowany: null,
      nowy: this.$inertia.form({ nazwa: '', dzielony: false, nocleg: false }),
      edycja: this.$inertia.form({ nazwa: '', dzielony: false, nocleg: false }),
    }
  },
  methods: {
    dodaj() {
      this.nowy.post('/typy-kosztow', { preserveScroll: true, onSuccess: () => this.nowy.reset() })
    },
    zacznij(t) {
      this.edytowany = t.id
      this.edycja.nazwa = t.nazwa
      this.edycja.dzielony = t.dzielony
      this.edycja.nocleg = t.nocleg
    },
    zapisz(t) {
      this.edycja.put(`/typy-kosztow/${t.id}`, { preserveScroll: true, onSuccess: () => { this.edytowany = null } })
    },
    usun(t) {
      if (t.uzyc > 0 || !confirm(`Usunąć typ „${t.nazwa}”?`)) return
      this.$inertia.delete(`/typy-kosztow/${t.id}`, { preserveScroll: true })
    },
  },
}
</script>
