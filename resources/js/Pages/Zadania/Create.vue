<template>
  <div>
    <Head title="Nowe zgłoszenie" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/zadania">Zadania</Link>
      <span class="font-medium text-indigo-400">/</span> Nowe zgłoszenie
    </h1>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.title" :error="form.errors.title" class="pb-8 pr-6 w-full" label="Tytuł" placeholder="np. Formularz kontaktowy nie wysyła maila" />
          <text-input v-model="form.url" :error="form.errors.url" class="pb-8 pr-6 w-full" label="Link do strony" placeholder="https://..." />

          <div class="pb-8 pr-6 w-full">
            <label class="form-label">Opis:</label>
            <textarea
              v-model="form.description"
              rows="5"
              class="form-textarea"
              :class="{ error: form.errors.description }"
              placeholder="Co się dzieje, na jakiej przeglądarce, jak to powtórzyć. Print screen możesz wkleić Ctrl+V wprost tutaj."
              @paste="onPaste"
            />
            <div v-if="form.errors.description" class="form-error">{{ form.errors.description }}</div>
          </div>

          <div class="pb-8 pr-6 w-full">
            <label class="form-label">Print screeny:</label>
            <screenshot-uploader ref="uploader" v-model="form.screenshots" />
            <div v-if="screenshotError" class="form-error">{{ screenshotError }}</div>
          </div>

          <select-input v-model="form.status" :error="form.errors.status" class="pb-8 pr-6 w-full lg:w-1/2" label="Status">
            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
          </select-input>

          <select-input v-model="form.priority" :error="form.errors.priority" class="pb-8 pr-6 w-full lg:w-1/2" label="Priorytet">
            <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
          </select-input>

          <select-input v-model="form.assignee_id" :error="form.errors.assignee_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Przypisz do">
            <option :value="null">— nikogo —</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select-input>

          <date-input v-model="form.deadline" :error="form.errors.deadline" class="pb-8 pr-6 w-full lg:w-1/2" label="Termin" />
        </div>

        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj zgłoszenie</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import DateInput from '@/Shared/DateInput'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'
import ScreenshotUploader from '@/Shared/ScreenshotUploader'
import SelectInput from '@/Shared/SelectInput'
import TextInput from '@/Shared/TextInput'

export default {
  components: {
    DateInput,
    Head,
    Link,
    LoadingButton,
    ScreenshotUploader,
    SelectInput,
    TextInput,
  },
  layout: Layout,
  props: {
    statuses: Array,
    priorities: Array,
    users: Array,
  },
  data() {
    return {
      form: this.$inertia.form({
        title: '',
        url: '',
        description: '',
        status: 'do_zrobienia',
        priority: 'normalny',
        assignee_id: null,
        deadline: null,
        screenshots: [],
      }),
    }
  },
  computed: {
    /** Błędy walidacji plików przychodzą jako screenshots.0, screenshots.1, ... */
    screenshotError() {
      const errors = this.form.errors

      return errors.screenshots || Object.keys(errors).filter((key) => key.startsWith('screenshots.')).map((key) => errors[key])[0]
    },
  },
  methods: {
    /** Print screen wklejony w opis od razu ląduje w uploaderze. */
    onPaste(event) {
      const files = Array.from(event.clipboardData?.files || [])

      if (files.length) {
        event.preventDefault()
        this.$refs.uploader.addFiles(files)
      }
    },
    store() {
      this.form.post('/zadania')
    },
  },
}
</script>
