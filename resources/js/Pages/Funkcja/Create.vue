<template>
  <div>
    <Head title="Create Contact" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/funkcja">Stanowisko</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa" />
          <!-- Do której kolumny listy budów trafia osoba z tym stanowiskiem.
               Trzymamy to w słowniku, bo lista stanowisk rośnie, a wcześniej
               kolumny były zaszyte w kodzie i gubiły nowe stanowiska. -->
          <select-input
            v-model="form.rola_budowy"
            :error="form.errors.rola_budowy"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Kolumna na liście budów"
          >
            <option :value="null">— nie pokazuj —</option>
            <option v-for="(podpis, klucz) in roleBudowy" :key="klucz" :value="klucz">{{ podpis }}</option>
          </select-input>
          <div class="pb-8 pr-6 w-full">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input v-model="form.kierownictwo" type="checkbox" class="form-checkbox" />
              <span>Stanowisko kierownicze — może być wybrane w zakładce Kierownictwo budowy</span>
            </label>
          </div>
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
import SelectInput from '@/Shared/SelectInput'

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
  data() {
    return {
      form: this.$inertia.form({
        name: '',
        kierownictwo: false,
        rola_budowy: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/funkcja')
    },
  },
}
</script>
