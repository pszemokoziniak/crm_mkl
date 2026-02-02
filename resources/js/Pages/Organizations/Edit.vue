<template>
  <div>
    <Head :title="form.nazwaBud" />
    <BudMenu :budId="budId" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">Budowa</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.nazwaBud }}
    </h1>
    <trashed-message v-if="organization.deleted_at" :user_owner="user_owner" class="mb-6" @restore="restore">Ta budowa jest usunięta</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.nazwaBud" :error="form.errors.nazwaBud" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Pełna Nazwa Budowy" />
          <text-input v-model="form.numerBud" :error="form.errors.numerBud" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Budowy" />
          <text-input v-model="form.city" :error="form.errors.city" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <text-input v-model="form.name" :error="form.errors.name" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Nazwa Klienta" />
          <text-input v-model="form.zaklad" :error="form.errors.zaklad" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Zakład podatkowy" />
          <select-input v-model="form.country_id" :error="form.errors.country_id" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj Budowy">
            <option v-for="item in krajTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.addressBud" :error="form.errors.addressBud" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Adres Budowy" />
          <text-input v-model="form.addressKwat" :error="form.errors.addressKwat" :disabled="flag" class="lg:w-1/1 pb-8 pr-6 w-full" label="Adres Kwatery" />
        </div>

        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <delete-button
            v-if="!organization.deleted_at && (user_owner === 1 || user_owner === 2)"
            :href="`/budowy/${organization.id}`"
            confirm="Jesteś pewien, że chcesz usunąć budowę?"
          >
            Usuń budowę
          </delete-button>
          <loading-button v-if="!organization.deleted_at" :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
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
import BudMenu from '@/Shared/BudMenu'
import DeleteButton from '@/Shared/DeleteButton'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    BudMenu,
    DeleteButton,
  },
  layout: Layout,
  props: {
    organization: Object,
    krajTyps: Object,
    kierownikBud: Object,
    user_owner: Number,
    flag: Boolean,
    inzyniers: Object,
  },
  remember: 'form',
  data() {
    return {
      budId: this.organization.id,
      checkedValues: [],
      toggle: false,
      button: {
        text: 'Dodaj pracownika',
      },
      form: this.$inertia.form({
        name: this.organization.name,
        nazwaBud: this.organization.nazwaBud,
        numerBud: this.organization.numerBud,
        city: this.organization.city,
        kierownikBud_id: this.organization.kierownikBud_id,
        zaklad: this.organization.zaklad,
        country_id: this.organization.country_id,
        addressBud: this.organization.addressBud,
        addressKwat: this.organization.addressKwat,
        inzynier_id: this.organization.inzynier_id,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/budowy/${this.organization.id}`)
    },
    restore() {
      if (confirm('Jesteś pewnien, że chcesz przywrócić budowę?')) {
        this.$inertia.put(`/budowy/${this.organization.id}/restore`)
      }
    },
    toggleSeen: function () {
      this.toggle = !this.toggle
      this.button.text = this.toggle ? 'Zamknij' : 'Dodaj pracownika'
    },
    created() {
      this.$inertia.post(`/contacts/${this.organization.id}/addPracownik`, this.checkedValues)
    },
  },
}
</script>
