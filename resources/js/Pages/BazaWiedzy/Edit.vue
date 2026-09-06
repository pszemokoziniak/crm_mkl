<template>
  <div>
    <Head :title="`Edycja: ${artykul.tytul}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/baza-wiedzy">Baza wiedzy</Link>
      <span class="text-indigo-400 font-medium">/</span>
      <Link class="text-indigo-400 hover:text-indigo-600" :href="`/baza-wiedzy/${artykul.id}`">{{ artykul.tytul }}</Link>
      <span class="text-indigo-400 font-medium">/</span> Edycja
    </h1>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="zapisz">
        <artykul-form :form="form" :kategorie="kategorie" />
        <div class="flex items-center justify-between px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button type="button" class="text-red-600 hover:underline" @click="usun">Usuń artykuł</button>
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Zapisz</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import ArtykulForm from './Form'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: { ArtykulForm, Head, Link, LoadingButton },
  layout: Layout,
  props: {
    artykul: { type: Object, required: true },
    kategorie: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: this.$inertia.form({
        tytul: this.artykul.tytul,
        kategoria: this.artykul.kategoria || '',
        tresc: this.artykul.tresc || '',
        tylko_admin: this.artykul.tylko_admin,
        kolejnosc: this.artykul.kolejnosc,
      }),
    }
  },
  methods: {
    zapisz() {
      this.form.put(`/baza-wiedzy/${this.artykul.id}`)
    },
    usun() {
      if (confirm('Usunąć ten artykuł?')) {
        this.$inertia.delete(`/baza-wiedzy/${this.artykul.id}`)
      }
    },
  },
}
</script>
