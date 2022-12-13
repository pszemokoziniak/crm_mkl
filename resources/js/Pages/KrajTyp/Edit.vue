<template>
  <div>
    <Head :title="`${form.name}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/krajTyp">Kraj</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>
     <trashed-message v-if="kraj.deleted_at" class="mb-6" @restore="restore">Usunąć?</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa" />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!kraj.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz</loading-button>
        </div>
      </form>
    </div>

    <h1 class="mt-8 mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/krajTyp">Skonfigurowane święta</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Data</th>
          <th class="pb-4 pt-6 px-6">Akcje</th>
        </tr>
        <tr v-for="feast in feasts" :key="feast.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/country/${feast.country_id}/feasts/${feast.id}`">
              {{ feast.name }}
            </Link>
          </td>
          <td class="border-t">{{ feast.date }}</td>
          <td class="border-t">
            <Link method='delete' class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/country/${feast.country_id}/feasts/${feast.id}/delete`">
              <Icon name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
        </tr>
      </table>
    </div>

  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import Icon from '@/Shared/Icon'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    TrashedMessage,
    Icon,
  },
  layout: Layout,
  props: {
    kraj: Object,
    feasts: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.kraj.id,
        name: this.kraj.name,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/krajTyp/${this.kraj.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/krajTyp/${this.kraj.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/krajTyp/${this.kraj.id}/restore`)
      }
    },
  },
}
</script>
