<template>
  <div>
    <Head title="Budowa" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/organizations">Budowa</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.numerBudowy" :error="form.errors.numerBudowy" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Budowy" />
          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa Budowy" />

          <select-input v-model="form.kierownikBud_id" :error="form.errors.kierownikBud_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kierownik Budowy">
<!--            <option v-for="item in kierownikBuds" :key="item.id" :value="item.id">{{ item.name }}</option>-->
            <option :value="null" />
            <option value="CA">Rysiek</option>
            <option value="US">Mietek</option>
          </select-input>

          <text-input v-model="form.zaklad" :error="form.errors.zaklad" class="pb-8 pr-6 w-full lg:w-1/2" label="Zakład podatkowy" />
          <text-input v-model="form.addressBud" :error="form.errors.addressBud" class="pb-8 pr-6 w-full lg:w-1/2" label="Adres Budowy" />
          <text-input v-model="form.addressKwatery" :error="form.errors.addressKwatery" class="pb-8 pr-6 w-full lg:w-1/2" label="Adres Kwatery" />
          <text-input v-model="form.postal_code" :error="form.errors.postal_code" class="pb-8 pr-6 w-full lg:w-1/2" label="Kod pocztowy" />
<!--          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />-->
<!--          <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" />-->
<!--          <text-input v-model="form.phone" :error="form.errors.phone" class="pb-8 pr-6 w-full lg:w-1/2" label="Telefon" />-->
<!--          <text-input v-model="form.region" :error="form.errors.region" class="pb-8 pr-6 w-full lg:w-1/2" label="Województow" />-->
          <select-input v-model="form.countryBuds" :error="form.errors.countryBuds" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj Budowy">
            <option :value="null" />
            <option value="CA">Polska</option>
            <option value="US">USA</option>
            <option value="US">Mongolia</option>
          </select-input>
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
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        numerBudowy: null,
        name: null,
        kierownikBud_id: null,
        zaklad: null,
        addressBud: null,
        addressKwatery: null,
        postal_code: null,
        city: null,
        country: null,
        // email: null,
        // phone: null,
        // region: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/organizations')
    },
  },
}
</script>
