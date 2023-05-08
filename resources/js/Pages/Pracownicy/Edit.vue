<template>
  <div>
    <Head :title="`${contact.last_name}`" />
    <BudMenu :budId="budId" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" :href="`/contacts/${contact.id}/edit`">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.last_name }} {{ contact.first_name }}
    </h1>
    <div class="max-w bg-white rounded-md shadow overflow-hidden">
      <h3 class="font-medium text-xl  p-4">Popraw czas pracy. Budowa: {{organization.nazwaBud}}</h3>
      <form @submit.prevent="update()">
        <div class="flex flex-wrap -mb-3 -mr-6 p-8">
          <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek pracy na budowie" />
          <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec pracy na budowie" />
<!--          <text-input type="hidden" value="@{{contact_id}}" v-model="form.contact_id" :error="form.errors.contact_id" />-->
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
<!--          <button class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>-->
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
import LoadingButton from '@/Shared/LoadingButton'
import BudMenu from '@/Shared/BudMenu.vue'
//
export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    BudMenu,
  },
  layout: Layout,
  props: {
    contactWorkDate: Object,
    contact: Object,
    organization: Object,
  },
  remember: 'form',
  data() {
    return {
      budId: this.organization.id,
      form: this.$inertia.form({
        id: this.contactWorkDate.id,
        start: this.contactWorkDate.start,
        end: this.contactWorkDate.end,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/pracownicy/${this.contactWorkDate.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/pracownicy/${this.contactWorkDate.id}`)
      }
    },
  },
}
</script>
