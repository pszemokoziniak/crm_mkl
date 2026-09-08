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
          <!-- Lista istniejących grup sprzętu + możliwość wpisania nowej,
               tak samo jak przy wyborze typu sprzętu. -->
          <select-input
            v-model="form.grupa_wybor"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Grupa (łączy modele w magazynie)"
          >
            <option value="">— bez grupy —</option>
            <option v-for="k in grupy" :key="k" :value="k">{{ k }}</option>
            <option value="__new__">+ Nowa grupa…</option>
          </select-input>
          <text-input
            v-if="form.grupa_wybor === '__new__'"
            v-model="form.grupa_nowa"
            :error="form.errors.grupa"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Nazwa nowej grupy"
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
    grupy: { type: Array, default: () => [] },
    narzedziaTyp: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.narzedziaTyp.id,
        name: this.narzedziaTyp.name,
        grupa: this.narzedziaTyp.grupa,
        grupa_wybor: this.narzedziaTyp.grupa || '',
        grupa_nowa: '',
      }),
    }
  },
  watch: {
    'form.grupa_wybor': function (wybor) {
      this.form.grupa = wybor === '__new__' ? this.form.grupa_nowa : wybor
    },
    'form.grupa_nowa': function (nazwa) {
      if (this.form.grupa_wybor === '__new__') {
        this.form.grupa = nazwa
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
