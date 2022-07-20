<template>
  <div>
    <Head :title="form.name" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/organizations">Budowa</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>
    <trashed-message v-if="organization.deleted_at" class="mb-6" @restore="restore">Ta budowa będzie usunięta</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.nazwaBud" :error="form.errors.nazwaBud" class="pb-8 pr-6 w-full lg:w-1/1" label="Pełna Nazwa Budowy" />
          <text-input v-model="form.numerBud" :error="form.errors.numerBud" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Budowy" />
          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa Budowy" />

          <select-input v-model="form.kierownikBud_id" :error="form.errors.kierownikBud_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kierownik Budowy">
            <option v-for="item in kierownikBud" :key="item.id" :value="item.id">{{ item.last_name }}</option>
<!--            <option :value="null" />-->
<!--            <option value="CA">Rysiek</option>-->
<!--            <option value="US">Mietek</option>-->
          </select-input>

          <text-input v-model="form.zaklad" :error="form.errors.zaklad" class="pb-8 pr-6 w-full lg:w-1/2" label="Zakład podatkowy" />
          <select-input v-model="form.country_id" :error="form.errors.country_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj Budowy">
            <option v-for="item in krajTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.addressBud" :error="form.errors.addressBud" class="pb-8 pr-6 w-full lg:w-1/1" label="Adres Budowy" />
          <text-input v-model="form.addressKwat" :error="form.errors.addressKwat" class="pb-8 pr-6 w-full lg:w-1/1" label="Adres Kwatery" />
        </div>

        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!organization.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
        </div>
      </form>
    </div>
    <h2 class="mt-12 text-2xl font-bold">Pracownik</h2>
    <div class="mt-6 bg-white rounded shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Imię</th>
          <th class="pb-4 pt-6 px-6">Nazwisko</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
        </tr>
        <tr v-for="contact in organization.contacts" :key="contact.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${contact.id}/edit`">
              {{ contact.name }}
              <icon v-if="contact.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
              {{ contact.country_id }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
              {{ contact.kierownikBud_id }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="organization.contacts.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'

export default {
  components: {
    Head,
    Icon,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
  },
  layout: Layout,
  props: {
    organization: Object,
    // kierownikBud_id: Object,
    // country_id: Object,
    krajTyps: Object,
    kierownikBud: Object,
  },
  remember: 'form',
  data() {
    return {
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
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/budowy/${this.organization.id}`)
    },
    destroy() {
      if (confirm('Jesteś pewnien, że chcesz usunąć budowę?')) {
        this.$inertia.delete(`/budowy/${this.organization.id}`)
      }
    },
    restore() {
      if (confirm('Jesteś pewnien, że chcesz przywrócić budowę?')) {
        this.$inertia.put(`/budowy/${this.organization.id}/restore`)
      }
    },
  },
}
</script>
