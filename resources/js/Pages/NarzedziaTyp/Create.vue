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
          <!-- Lista już używanych kategorii + możliwość wpisania nowej,
               tak samo jak przy wyborze typu sprzętu. -->
          <select-input
            v-model="form.kategoria_wybor"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Kategoria (grupuje modele w magazynie)"
          >
            <option value="">— bez kategorii —</option>
            <option v-for="k in kategorie" :key="k" :value="k">{{ k }}</option>
            <option value="__new__">+ Nowa kategoria…</option>
          </select-input>
          <text-input
            v-if="form.kategoria_wybor === '__new__'"
            v-model="form.kategoria_nowa"
            :error="form.errors.kategoria"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Nazwa nowej kategorii"
            placeholder="np. Żuraw"
          />
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
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    SelectInput,
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
        kategoria_wybor: '',
        kategoria_nowa: '',
      }),
    }
  },
  watch: {
    'form.kategoria_wybor': function (wybor) {
      this.form.kategoria = wybor === '__new__' ? this.form.kategoria_nowa : wybor
    },
    'form.kategoria_nowa': function (nazwa) {
      if (this.form.kategoria_wybor === '__new__') {
        this.form.kategoria = nazwa
      }
    },
  },
  methods: {
    store() {
      this.form.post('/narzedziaTyp')
    },
  },
}
</script>
