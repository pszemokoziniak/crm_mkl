<template>
  <div>
    <Head title="Klient" />
    <BudMenu :budId="budId" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">Klient</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.nameFirma" :error="form.errors.nameFirma" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa Firmy" />
          <text-input v-model="form.adres" :error="form.errors.adres" class="pb-8 pr-6 w-full lg:w-1/2" label="Adres" />
          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <select-input v-model="form.country_id" :error="form.errors.country_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj">
            <option v-for="item in krajTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.nameKontakt" :error="form.errors.nameKontakt" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa kontaktu" />
          <text-input v-model="form.phone" :error="form.errors.phone" class="pb-8 pr-6 w-full lg:w-1/2" label="Nr. telefonu" />
          <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" />
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
import BudMenu from '@/Shared/BudMenu'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    BudMenu,
  },
  layout: Layout,
  props: {
    budId: Number,
    krajTyps: Object,
    account: Object,
    errors: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        nameFirma: null,
        organization_id: this.budId,
        adres: null,
        city: null,
        country_id: null,
        nameKontakt: null,
        phone: null,
        email: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/klient')
    },
  },
}
</script>
