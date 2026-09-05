<template>
  <div>
    <Head :title="`${form.id} ${form.name}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/narzedziaTyp">Narzędzia Typ</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>
     <trashed-message v-if="narzedziaTyp.deleted_at" class="mb-6" @restore="restore">Usuniąć?</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/1" label="Nazwa" />
          <!-- Lista już używanych kategorii + możliwość wpisania nowej,
               tak samo jak przy wyborze typu sprzętu. -->
          <select-input
            v-model="form.kategoria_wybor"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Kategoria (grupuje modele w magazynie)"
          >
            <option value="">— bez kategorii —</option>
            <option v-for="k in kategorie" :key="k" :value="k">{{ k }}</option>
            <option value="__new__">+ Nowa kategoria…</option>
          </select-input>
          <text-input
            v-if="form.kategoria_wybor === '__new__'"
            v-model="form.kategoria_nowa"
            :error="form.errors.kategoria"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Nazwa nowej kategorii"
            placeholder="np. Żuraw"
          />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!narzedziaTyp.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
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

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    SelectInput,
    TrashedMessage,
  },
  layout: Layout,
  props: {
    kategorie: { type: Array, default: () => [] },
    narzedziaTyp: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.narzedziaTyp.id,
        name: this.narzedziaTyp.name,
        kategoria: this.narzedziaTyp.kategoria,
        kategoria_wybor: this.narzedziaTyp.kategoria || '',
        kategoria_nowa: '',
      }),
    }
  },
  watch: {
    'form.kategoria_wybor': function (wybor) {
      this.form.kategoria = wybor === '__new__' ? this.form.kategoria_nowa : wybor
    },
    'form.kategoria_nowa': function (nazwa) {
      if (this.form.kategoria_wybor === '__new__') {
        this.form.kategoria = nazwa
      }
    },
  },
  methods: {
    update() {
      this.form.put(`/narzedziaTyp/${this.narzedziaTyp.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/narzedziaTyp/${this.narzedziaTyp.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/narzedziaTyp/${this.narzedziaTyp.id}/restore`)
      }
    },
  },
}
</script>
