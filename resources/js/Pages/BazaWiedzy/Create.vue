<template>
  <div>
    <Head title="Nowy artykuł" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/baza-wiedzy">Baza wiedzy</Link>
      <span class="text-indigo-400 font-medium">/</span> Nowy artykuł
    </h1>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="zapisz">
        <artykul-form :form="form" :kategorie="kategorie" />
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj</loading-button>
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
  remember: 'form',
  props: {
    kategorie: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: this.$inertia.form({
        tytul: '',
        kategoria: '',
        tresc: '',
        tylko_admin: false,
        kolejnosc: 0,
      }),
    }
  },
  methods: {
    zapisz() {
      this.form.post('/baza-wiedzy')
    },
  },
}
</script>
