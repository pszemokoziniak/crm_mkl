<template>
  <div>
    <Head title="Narzedzia Typ" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/narzedziaTyp">Narzędzia Typ</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa" />
          <!-- Kategoria zbiera modele w magazynie: "Kontener" obejmie
               Kontener 3m i 6m. Puste = typ stoi w magazynie osobno. -->
          <text-input
            v-model="form.kategoria"
            :error="form.errors.kategoria"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Kategoria (np. Kontener, Manitou)"
            list="lista-kategorii-sprzetu"
            placeholder="puste = bez kategorii"
          />
          <datalist id="lista-kategorii-sprzetu">
            <option v-for="k in kategorie" :key="k" :value="k" />
          </datalist>
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
  },
  layout: Layout,
  remember: 'form',
  props: {
    kategorie: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: this.$inertia.form({
        name: '',
        kategoria: '',
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/narzedziaTyp')
    },
  },
}
</script>
