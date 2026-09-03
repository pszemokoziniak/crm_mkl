<template>
  <div>
    <Head title="Generowanie umowy" />
    <h1 class="mb-2 text-2xl font-bold sm:text-3xl">
      <Link class="text-indigo-400 hover:text-indigo-600" :href="`/contacts/${contact.id}/edit`">{{ contact.imie_nazwisko }}</Link>
      <span class="font-medium text-indigo-400">/</span> Umowa
    </h1>
    <p class="mb-6 text-sm text-gray-500">
      Dane pracownika i budowy wypełniły się z systemu. Popraw, co trzeba, i pobierz dokument.
    </p>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap -mb-8 -mr-6 p-8">
        <select-input v-model="form.rodzaj" class="pb-8 pr-6 w-full lg:w-1/2" label="Rodzaj dokumentu">
          <option v-for="rodzaj in rodzaje" :key="rodzaj.value" :value="rodzaj.value">{{ rodzaj.label }}</option>
        </select-input>
        <text-input v-model="form.pracodawca" class="pb-8 pr-6 w-full lg:w-1/2" label="Pracodawca" />

        <text-input v-model="form.stanowisko" class="pb-8 pr-6 w-full lg:w-1/2" label="Stanowisko" />
        <div class="pb-8 pr-6 w-full lg:w-1/2">
          <label class="form-label">Budowa:</label>
          <select v-model="form.budowa" class="form-select mt-1 w-full">
            <option value="">— bez budowy —</option>
            <option v-for="b in budowy" :key="b.id" :value="b.nazwaBud">{{ b.nazwaBud }}</option>
          </select>
        </div>

        <date-input v-model="form.od" class="pb-8 pr-6 w-full lg:w-1/2" label="Od" />
        <date-input v-model="form.do" class="pb-8 pr-6 w-full lg:w-1/2" label="Do" />

        <text-input v-model="form.wynagrodzenie" class="pb-8 pr-6 w-full lg:w-1/2" label="Wynagrodzenie (opcjonalnie)" />
        <text-input v-model="form.miejsce" class="pb-8 pr-6 w-full lg:w-1/4" label="Miejscowość" />
        <date-input v-model="form.data_zawarcia" class="pb-8 pr-6 w-full lg:w-1/4" label="Data zawarcia" />

        <div class="pb-8 pr-6 w-full">
          <label class="form-label">Pozostałe warunki:</label>
          <textarea v-model="form.warunki" rows="2" class="form-textarea" />
        </div>
        <div class="pb-8 pr-6 w-full">
          <label class="form-label">Uwagi (opcjonalnie):</label>
          <textarea v-model="form.uwagi" rows="2" class="form-textarea" />
        </div>
      </div>

      <div class="flex flex-col gap-3 px-8 py-4 bg-gray-50 border-t border-gray-100 sm:flex-row sm:justify-end">
        <a :href="adres('podglad')" target="_blank" class="px-4 py-2 text-sm font-medium text-center text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
          Podgląd / drukuj (PDF)
        </a>
        <a :href="adres('doc')" class="btn-indigo text-center">Pobierz .doc</a>
      </div>
    </div>

    <p class="mt-4 max-w-3xl text-xs text-gray-500">
      PDF zapisujesz z podglądu: przeglądarka otwiera okno drukowania, gdzie wybierasz „Zapisz jako PDF".
      Plik .doc otwiera się w Wordzie i można go dalej edytować.
    </p>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import DateInput from '@/Shared/DateInput'
import Layout from '@/Shared/Layout'
import SelectInput from '@/Shared/SelectInput'
import TextInput from '@/Shared/TextInput'

export default {
  components: {
    DateInput,
    Head,
    Link,
    SelectInput,
    TextInput,
  },
  layout: Layout,
  props: {
    contact: Object,
    rodzaje: Array,
    domyslne: Object,
    budowy: Array,
  },
  data() {
    return {
      form: {
        rodzaj: this.domyslne.rodzaj,
        pracodawca: 'MKL-BAU Sp. z o.o.',
        stanowisko: this.domyslne.stanowisko || '',
        budowa: this.domyslne.budowa || '',
        od: this.domyslne.od || '',
        do: this.domyslne.do || '',
        wynagrodzenie: '',
        miejsce: this.domyslne.miejsce,
        data_zawarcia: this.domyslne.data_zawarcia,
        warunki: '',
        uwagi: this.domyslne.uwagi || '',
      },
    }
  },
  methods: {
    /** Dane lecą w adresie, więc podgląd i .doc otwierają się bez zapisu w bazie. */
    adres(co) {
      const params = new URLSearchParams()

      Object.entries(this.form).forEach(([klucz, wartosc]) => {
        if (wartosc) {
          params.set(klucz, wartosc)
        }
      })

      return `/contacts/${this.contact.id}/umowa/${co}?${params.toString()}`
    },
  },
}
</script>
