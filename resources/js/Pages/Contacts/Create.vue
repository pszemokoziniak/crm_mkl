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
          <text-input type="number" v-model="form.pesel" :error="form.errors.pesel" class="pb-8 pr-6 w-full lg:w-1/2" label="PESEL" />

          <text-input v-model="form.address" :error="form.errors.address" class="pb-8 pr-6 w-full lg:w-1/1" label="Miejsce zamieszkania" />
          <text-input v-model="form.miejsce_urodzenia" :error="form.errors.miejsce_urodzenia" class="pb-8 pr-6 w-full lg:w-1/1" label="Miejsce urodzenia" />

          <text-input v-model="form.idCard_number" :error="form.errors.idCard_number" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Dowodu" />
          <text-input type="date" v-model="form.idCard_date" :error="form.errors.idCard_date" class="pb-8 pr-6 w-full lg:w-1/2" label="Data ważności dowodu" />

          <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" />
          <text-input v-model="form.phone" :error="form.errors.phone" class="pb-8 pr-6 w-full lg:w-1/2" type="tel" label="Telefon" />

          <select-input v-model="form.funkcja_id" :error="form.errors.funkcja_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Stanowisko">
            <option v-for="funkcja in funkcjas" :key="funkcja.id" :value="funkcja.id">{{ funkcja.name }}</option>
          </select-input>

          <file-input v-model="form.photo_path" :error="form.errors.photo_path" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Zdjęcie" />

          <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Umowa o pracę</label>
          <text-input type="date" v-model="form.work_start" :error="form.errors.work_start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek umowy" />
          <text-input type="date" v-model="form.work_end" :error="form.errors.work_end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec umowy" />

          <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Ekuz</label>
          <text-input type="date" v-model="form.ekuz" :error="form.errors.ekuz" class="pb-8 pr-6 w-full lg:w-1/2" label="Termin ważności" />
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
import FileInput from '@/Shared/FileInput'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    FileInput,
  },
  layout: Layout,
  props: {
    organizations: Array,
    accounts: Array,
    funkcjas: Object,
    errors: Object,
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
        // position: '',
        funkcja_id: '',
        work_start: '',
        work_end: '',
        ekuz: '',
        organization_id: null,
        email: '',
        phone: '',
        address: '',
        miejsce_urodzenia: '',
        photo_path: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/contacts', {
        onSuccess: () => this.form.reset('photo_path'),
      })
    },
  },
}
</script>
