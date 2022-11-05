<template>
  <div>
    <Head :title="`${form.name}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/dokumentyTyp">Dokumenty Typ</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>
    <!-- <trashed-message v-if="funkcja.deleted_at" class="mb-6" @restore="restore">Usuniąć?</trashed-message> -->
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa" />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="form.id !== 1 && form.id !== 2 && form.id !== 3 && form.id !== 4 && form.id !== 5" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
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

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
  },
  layout: Layout,
  props: {
    dokumentTyp: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.dokumentTyp.id,
        name: this.dokumentTyp.name,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/dokumentyTyp/${this.dokumentTyp.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/dokumentyTyp/${this.dokumentTyp.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/dokumentyTyp/${this.dokumentTyp.id}/restore`)
      }
    },
  },
}
</script>
