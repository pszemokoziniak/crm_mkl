<template>
  <div>
    <Head title="Create Contact" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownicy</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.first_name" :error="form.errors.first_name" class="pb-8 pr-6 w-full lg:w-1/2" label="Imię" />
          <text-input v-model="form.last_name" :error="form.errors.last_name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwisko" />

          <text-input type="date" v-model="form.birth_date" :error="form.errors.birth_date" class="pb-8 pr-6 w-full lg:w-1/2" label="Data Urodzenia" />
          <text-input v-model="form.pesel" :error="form.errors.pesel" class="pb-8 pr-6 w-full lg:w-1/2" label="PESEL" />

          <text-input v-model="form.address" :error="form.errors.address" class="pb-8 pr-6 w-full lg:w-1/1" label="Miejsce zamieszkania" />

          <text-input v-model="form.idCard_number" :error="form.errors.idCard_number" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Dowodu" />
          <text-input type="date" v-model="form.idCard_date" :error="form.errors.idCard_date" class="pb-8 pr-6 w-full lg:w-1/2" label="Data ważności dowodu" />

          <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" />
          <text-input v-model="form.phone" :error="form.errors.phone" class="pb-8 pr-6 w-full lg:w-1/2" label="Telefon" />

          <select-input v-model="form.position" :error="form.errors.position" class="pb-8 pr-6 w-full lg:w-1/2" label="Stanowisko">
            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</option>
          </select-input>

          <select-input v-model="form.funkcja" :error="form.errors.funkcja" class="pb-8 pr-6 w-full lg:w-1/2" label="Funkcja">
            <option v-for="funkcja in funkcjas" :key="funkcja.id" :value="funkcja.id">{{ funkcja.name }}</option>
          </select-input>

          <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Umowa o pracę</label>
            <text-input type="date" v-model="form.work_start" :error="form.errors.work_start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek umowy" />
            <text-input type="date" v-model="form.work_end" :error="form.errors.work_end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec umowy" />
          
          <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Ekuz</label>
            <text-input type="date" v-model="form.ekuz" :error="form.errors.ekuz" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek umowy" />



          <!-- <text-input v-model="form.function" :error="form.errors.function" class="pb-8 pr-6 w-full lg:w-1/2" label="Funkcja" /> -->
          <!-- <text-input v-model="form.function" :error="form.errors.function" class="pb-8 pr-6 w-full lg:w-1/2" label="Umowa o pracę" /> -->
          <!-- <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Języki obce</label>

          <select-input v-model="form.lang" :error="form.errors.lang" class="pb-8 pr-6 w-full lg:w-1/2" label="Języki obce">
            <option :value="null" />
            <option value="PL">polski</option>
            <option value="EN">angielski</option>
            <option value="DE">niemiecki</option>
          </select-input> -->







          <!-- <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <text-input v-model="form.region" :error="form.errors.region" class="pb-8 pr-6 w-full lg:w-1/2" label="Województwo" />
          <select-input v-model="form.country" :error="form.errors.country" class="pb-8 pr-6 w-full lg:w-1/2" label="Państwo">
            <option :value="null" />
            <option value="PL">Polska</option>
            <option value="US">USA</option>
          </select-input>
          <text-input v-model="form.postal_code" :error="form.errors.postal_code" class="pb-8 pr-6 w-full lg:w-1/2" label="Kod pocztowy" /> -->
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj Pracownika</loading-button>
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
    organizations: Array,
    accounts: Array,
    funkcjas: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        first_name: '',
        last_name: '',
        birth_date: '',

        pesel: '',
        idCard_number: '',
        idCard_date: '',
        position: '',
        funkcja: '',
        work_start: '',
        work_end: '',
        ekuz: '',

        organization_id: null,
        email: '',
        phone: '',
        address: '',
        // city: '',
        // region: '',
        // country: '',
        // postal_code: '',
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/contacts')
    },
  },
}
</script>
