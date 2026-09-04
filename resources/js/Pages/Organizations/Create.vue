<template>
  <div>
    <Head title="Budowa" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">Budowa</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.nazwaBud" :error="form.errors.nazwaBud" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa Projektu" />
          <text-input v-model="form.numerBud" :error="form.errors.numerBud" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Projektu" />
          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <client-picker v-model="form.name" v-model:clientId="form.crm_client_id" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa Klienta" />
          <select-input v-model="form.zaklad" :error="form.errors.zaklad" class="pb-8 pr-6 w-full lg:w-1/2" label="Zakład podatkowy">
            <option :value="null">—</option>
            <option value="TAK">TAK</option>
            <option value="NIE">NIE</option>
          </select-input>
          <text-input
            v-model="form.kierownik_projektu"
            :error="form.errors.kierownik_projektu"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Kierownik projektu (osoba odpowiedzialna za kontrakt)"
            list="lista-kierownikow-projektu"
            placeholder="imię i nazwisko"
          />
          <!-- Podpowiedzi z nazwisk już użytych na innych budowach -->
          <datalist id="lista-kierownikow-projektu">
            <option v-for="osoba in kierownicyProjektow" :key="osoba" :value="osoba" />
          </datalist>
          <select-input v-model="form.country_id" :error="form.errors.country_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj Budowy">
            <option v-for="item in krajTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.addressBud" :error="form.errors.addressBud" class="pb-8 pr-6 w-full lg:w-1/1" label="Adres Budowy" />
          <text-input v-model="form.addressKwat" :error="form.errors.addressKwat" class="pb-8 pr-6 w-full lg:w-1/1" label="Adres Kwatery" />
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
import ClientPicker from '@/Shared/ClientPicker'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    ClientPicker,
  },
  layout: Layout,
  props: {
    kierownikBud: Object,
    krajTyps: Object,
    kierownicyProjektow: { type: Array, default: () => [] },
    inzyniers: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        nazwaBud: null,
        numerBud: null,
        name: null,
        crm_client_id: null,
        city: null,
        kierownikBud_id: null,
        inzynier_id: null,
        zaklad: null,
        kierownik_projektu: '',
        country_id: null,
        addressBud: null,
        addressKwat: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/budowy')
    },
  },
}
</script>
