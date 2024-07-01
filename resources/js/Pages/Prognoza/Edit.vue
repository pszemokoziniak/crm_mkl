<template>
  <div>
    <Head :title="Prognoza" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/prognoza">Prognoza</Link>
      <span class="text-indigo-400 font-medium">/</span>
      Popraw godziny
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <p class="py-2 font-bold">{{ prognoza.prognoza_dates_id[0].start }} - {{ prognoza.prognoza_dates_id[0].end }}</p>
          <text-input v-model="form.workers_count" :error="form.errors.workers_count" class="pb-8 pr-6 w-full lg:w-1/1" label="Ilość pracowników" />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
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
    prognoza: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.prognoza.id,
        workers_count: this.prognoza.workers_count,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/prognoza/${this.prognoza.id}`)
    },
    // destroy() {
    //   if (confirm('Chcesz usunąć?')) {
    //     this.$inertia.delete(`/prognoza/${this.bhp.id}`)
    //   }
    // },
    // restore() {
    //   if (confirm('Chcesz przywrócić?')) {
    //     this.$inertia.put(`/bhpTyp/${this.bhp.id}/restore`)
    //   }
    // },
  },
}
</script>
