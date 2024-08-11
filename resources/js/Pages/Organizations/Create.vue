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
          <text-input v-model="form.nazwaBud" :error="form.errors.nazwaBud" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa Budowy" />
          <text-input v-model="form.numerBud" :error="form.errors.numerBud" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Budowy" />
          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa Klienta" />
          <select-input v-model="form.kierownikBud_id" :error="form.errors.kierownikBud_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kierownik Budowy">
            <option value="0">Brak</option>
            <option v-for="item in kierownikBud" :key="item.id" :value="item.id">{{ item.last_name }}</option>
          </select-input>
          <select-input v-model="form.inzynier_id" :error="form.errors.inzynier_id" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Inżynier Budowy">
            <option v-for="item in inzyniers" :key="item.id" :value="item.id">{{ item.last_name }} {{ item.first_name }}</option>
          </select-input>
          <text-input v-model="form.zaklad" :error="form.errors.zaklad" class="pb-8 pr-6 w-full lg:w-1/2" label="Zakład podatkowy" />
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

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
  },
  layout: Layout,
  props: {
    kierownikBud: Object,
    krajTyps: Object,
    inzyniers: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        nazwaBud: null,
        numerBud: null,
        name: null,
        city: null,
        kierownikBud_id: null,
        inzynier_id: null,
        zaklad: null,
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
