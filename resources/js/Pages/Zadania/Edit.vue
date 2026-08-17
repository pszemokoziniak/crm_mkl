<template>
  <div>
    <Head :title="`Popraw: ${form.title}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/zadania">Zadania</Link>
      <span class="font-medium text-indigo-400">/</span>
      <Link class="text-indigo-400 hover:text-indigo-600" :href="`/zadania/${zadanie.id}`">{{ zadanie.title }}</Link>
      <span class="font-medium text-indigo-400">/</span> Popraw
    </h1>

    <trashed-message v-if="zadanie.deleted_at" class="mb-6" :user_owner="$page.props.auth.user.owner" @restore="restore">
      To zgłoszenie jest zarchiwizowane.
    </trashed-message>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.title" :error="form.errors.title" class="pb-8 pr-6 w-full" label="Tytuł" />
          <text-input v-model="form.url" :error="form.errors.url" class="pb-8 pr-6 w-full" label="Link do strony" placeholder="https://..." />

          <div class="pb-8 pr-6 w-full">
            <label class="form-label">Opis:</label>
            <textarea
              v-model="form.description"
              rows="5"
              class="form-textarea"
              :class="{ error: form.errors.description }"
              @paste="onPaste"
            />
            <div v-if="form.errors.description" class="form-error">{{ form.errors.description }}</div>
          </div>

          <div v-if="zadanie.screenshots.length" class="pb-8 pr-6 w-full">
            <label class="form-label">Zapisane print screeny:</label>
            <div class="grid grid-cols-2 gap-3 mt-2 sm:grid-cols-4">
              <div v-for="file in zadanie.screenshots" :key="file.id" class="relative group">
                <a :href="file.url" target="_blank">
                  <img v-if="file.is_image" :src="file.url" :alt="file.name" class="w-full h-24 object-cover rounded border border-gray-200 hover:opacity-75 transition-opacity" />
                  <div v-else class="flex items-center justify-center px-2 w-full h-24 text-xs text-center text-gray-500 bg-gray-50 rounded border border-gray-200">
                    {{ file.name }}
                  </div>
                </a>
                <button
                  type="button"
                  class="absolute top-1 right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full opacity-90 hover:bg-red-600"
                  title="Usuń załącznik"
                  @click="removeFile(file)"
                >
                  ×
                </button>
              </div>
            </div>
          </div>

          <div class="pb-8 pr-6 w-full">
            <label class="form-label">Dodaj print screeny:</label>
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

        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <delete-button v-if="!zadanie.deleted_at" :href="`/zadania/${zadanie.id}`" confirm="Zarchiwizować to zgłoszenie?">
            Archiwizuj
          </delete-button>
          <button v-else type="button" class="text-sm text-gray-600 hover:underline" @click="restore">Przywróć z archiwum</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz zmiany</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import DateInput from '@/Shared/DateInput'
import DeleteButton from '@/Shared/DeleteButton'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'
import ScreenshotUploader from '@/Shared/ScreenshotUploader'
import SelectInput from '@/Shared/SelectInput'
import TextInput from '@/Shared/TextInput'
import TrashedMessage from '@/Shared/TrashedMessage'

export default {
  components: {
    DateInput,
    DeleteButton,
    Head,
    Link,
    LoadingButton,
    ScreenshotUploader,
    SelectInput,
    TextInput,
    TrashedMessage,
  },
  layout: Layout,
  props: {
    zadanie: Object,
    statuses: Array,
    priorities: Array,
    users: Array,
  },
  data() {
    return {
      form: this.$inertia.form({
        title: this.zadanie.title,
        url: this.zadanie.url,
        description: this.zadanie.description,
        status: this.zadanie.status,
        priority: this.zadanie.priority,
        assignee_id: this.zadanie.assignee_id,
        deadline: this.zadanie.deadline,
        screenshots: [],
      }),
    }
  },
  computed: {
    screenshotError() {
      const errors = this.form.errors

      return errors.screenshots || Object.keys(errors).filter((key) => key.startsWith('screenshots.')).map((key) => errors[key])[0]
    },
  },
  methods: {
    onPaste(event) {
      const files = Array.from(event.clipboardData?.files || [])

      if (files.length) {
        event.preventDefault()
        this.$refs.uploader.addFiles(files)
      }
    },
    update() {
      // Print screeny wymuszają FormData, a PHP nie parsuje multipart przy PUT —
      // dlatego wysyłamy POST z _method, a Laravel podmienia metodę na PUT.
      this.form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(`/zadania/${this.zadanie.id}`)
    },
    removeFile(file) {
      if (!confirm('Usunąć ten załącznik?')) {
        return
      }

      this.$inertia.delete(`/zadania/${this.zadanie.id}/files/${file.id}`, { preserveScroll: true })
    },
    restore() {
      this.$inertia.put(`/zadania/${this.zadanie.id}/restore`)
    },
  },
}
</script>
