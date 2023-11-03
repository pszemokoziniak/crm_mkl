<template>
  <div>
    <Head :title="`${form.title}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/shiftStatusTyp">Typ nieobecności w pracy</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.title }}
    </h1>
     <trashed-message v-if="shiftStatus.deleted_at" class="mb-6" @restore="restore">Przywrócić?</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.title" :error="form.errors.title" class="pb-8 pr-6 w-1/2 lg:w-1/1" label="Nazwa" />
          <text-input v-model="form.code" :error="form.errors.code" class="pb-8 pr-6 w-1/2 lg:w-1/1" label="Code" />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!shiftStatus.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz</loading-button>
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
import TrashedMessage from '@/Shared/TrashedMessage'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    TrashedMessage,
  },
  layout: Layout,
  props: {
    shiftStatus: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.shiftStatus.id,
        title: this.shiftStatus.title,
        code: this.shiftStatus.code,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/shiftStatusTyp/${this.shiftStatus.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/shiftStatusTyp/${this.shiftStatus.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/shiftStatusTyp/${this.shiftStatus.id}/restore`)
      }
    },
  },
}
</script>
