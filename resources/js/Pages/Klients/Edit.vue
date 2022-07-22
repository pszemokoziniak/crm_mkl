<template>
  <div>
    <Head :title="form.name" />
    <BudMenu :budId="budId" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/organizations">Klient</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.nameFirma }}
    </h1>
    <trashed-message v-if="klient.deleted_at" class="mb-6" @restore="restore">Ta budowa będzie usunięta</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.nameFirma" :error="form.errors.nameFirma" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa Firmy" />
          <text-input v-model="form.adres" :error="form.errors.adres" class="pb-8 pr-6 w-full lg:w-1/2" label="Adres" />
          <text-input v-model="form.city" :error="form.errors.city" class="pb-8 pr-6 w-full lg:w-1/2" label="Miasto" />
          <select-input v-model="form.country_id" :error="form.errors.country_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj">
            <option v-for="item in krajTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.nameKontakt" :error="form.errors.nameKontakt" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa kontaktu" />
          <text-input v-model="form.phone" :error="form.errors.phone" class="pb-8 pr-6 w-full lg:w-1/2" label="Nr. telefonu" />
          <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" />
        </div>

        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!klient.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
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
        <tr v-for="contact in klient" :key="contact.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
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
<!--        <tr v-if="contact.id.length === 0">-->
<!--          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>-->
<!--        </tr>-->
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
import BudMenu from '@/Shared/BudMenu'

export default {
  components: {
    Head,
    Icon,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    BudMenu,
  },
  layout: Layout,
  props: {
    klient: Object,
    krajTyps: Object,
    kierownikBud: Object,
    budId: Number,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        organization_id: this.klient.organization_id,
        nameFirma: this.klient.nameFirma,
        adres: this.klient.adres,
        city: this.klient.city,
        country_id: this.klient.country_id,
        nameKontakt: this.klient.nameKontakt,
        phone: this.klient.phone,
        email: this.klient.email,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/klient/${this.klient.id}`)
    },
    destroy() {
      if (confirm('Jesteś pewnien, że chcesz usunąć budowę?')) {
        this.$inertia.delete(`/klient/${this.klient.id}`)
      }
    },
    restore() {
      if (confirm('Jesteś pewnien, że chcesz przywrócić budowę?')) {
        this.$inertia.put(`/klient/${this.klient.id}/restore`)
      }
    },
  },
}
</script>
