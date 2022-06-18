<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />
    <div>
      <WorkerMenu :contactId="contactId" />
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.first_name }} {{ form.last_name }}
    </h1>
    <trashed-message v-if="contact.deleted_at" class="mb-6" @restore="restore"> Ten pracownik będzię usunięty</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
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

          <select-input v-model="form.funkcja_id" :error="form.errors.funkcja_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Funkcja">
            <option v-for="funkcja in funkcjas" :key="funkcja.id" :value="funkcja.id">{{ funkcja.name }}</option>
          </select-input>

          <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Umowa o pracę</label>
            <text-input type="date" v-model="form.work_start" :error="form.errors.work_start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek umowy" />
            <text-input type="date" v-model="form.work_end" :error="form.errors.work_end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec umowy" />

          <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Ekuz</label>
            <text-input type="date" v-model="form.ekuz" :error="form.errors.ekuz" class="pb-8 pr-6 w-full lg:w-1/2" label="Ważne do" />
        </div>


        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!contact.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
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
import TrashedMessage from '@/Shared/TrashedMessage'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    contact: Object,
    organizations: Array,
    funkcjas: Object,
    accounts: Object,
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      form: this.$inertia.form({
        first_name: this.contact.first_name,
        last_name: this.contact.last_name,
        organization_id: this.contact.organization_id,
        email: this.contact.email,
        phone: this.contact.phone,
        address: this.contact.address,
        contactId: this.contact.id,

        birth_date: this.contact.birth_date,
        pesel: this.contact.pesel,
        idCard_number: this.contact.idCard_number,
        idCard_date: this.contact.idCard_date,
        position: this.contact.position,
        funkcja_id: this.contact.funkcja_id,
        work_start: this.contact.work_start,
        work_end: this.contact.work_end,
        ekuz: this.contact.ekuz,
        // city: this.contact.city,
        // region: this.contact.region,
        // country: this.contact.country,
        // postal_code: this.contact.postal_code,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/contacts/${this.contact.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/contacts/${this.contact.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/contacts/${this.contact.id}/restore`)
      }
    },
  },
}
</script>
