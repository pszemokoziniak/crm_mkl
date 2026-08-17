<template>
  <div class="bg-white rounded-md shadow overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-100">
      <div>
        <h2 class="text-lg font-bold text-gray-800">Dyskusja</h2>
        <p class="text-[11px] text-gray-500">
          Wpisz <code class="px-1 bg-gray-100 rounded">@</code> żeby kogoś wywołać — dostanie powiadomienie w dzwonku.
        </p>
      </div>
      <span class="text-xs font-medium text-gray-400">
        {{ commentCount }} {{ commentCount === 1 ? 'komentarz' : 'komentarzy' }}
      </span>
    </div>

    <div v-if="canComment" class="p-6 bg-gray-50/40 border-b border-gray-100">
      <mention-textarea
        v-model="form.body"
        :users="mentionableUsers"
        :rows="3"
        @paste-files="addFiles"
      />
      <div class="mt-3">
        <screenshot-uploader ref="uploader" v-model="form.files" />
      </div>
      <div class="flex items-center justify-end mt-3">
        <loading-button :loading="form.processing" class="btn-indigo" type="button" :disabled="!form.body.trim()" @click="submit">
          Dodaj komentarz
        </loading-button>
      </div>
    </div>

    <div class="divide-y divide-gray-100">
      <p v-if="notes.length === 0" class="p-8 text-center text-sm text-gray-400 italic">
        Brak komentarzy. Zacznij dyskusję.
      </p>

      <template v-for="note in notes" :key="note.id">
        <!-- Wpis systemowy (np. zmiana statusu) -->
        <div v-if="note.system" class="flex items-center gap-2 px-6 py-2 text-xs text-gray-500 bg-gray-50/60">
          <span class="w-1.5 h-1.5 bg-gray-300 rounded-full" />
          <span class="font-medium">{{ note.author ? note.author.name : 'System' }}</span>
          <span>{{ note.body }}</span>
          <span class="text-gray-400">{{ note.created_at }}</span>
        </div>

        <div v-else class="flex items-start gap-3 p-6">
          <span class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-xs font-bold text-indigo-700 bg-indigo-100 rounded-full">
            {{ initials(note.author ? note.author.name : '?') }}
          </span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-sm font-bold text-gray-800">{{ note.author ? note.author.name : 'Nieznany' }}</span>
              <span class="text-[10px] text-gray-400">{{ note.created_at }}</span>
              <span v-if="note.edited" class="text-[10px] text-gray-300 italic">(edytowano {{ note.updated_at }})</span>
            </div>

            <div v-if="editingId !== note.id">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="text-sm text-gray-700 whitespace-pre-wrap break-words" v-html="renderBody(note.body)" />

              <div v-if="note.files.length" class="flex flex-wrap gap-2 mt-3">
                <a v-for="file in note.files" :key="file.id" :href="file.url" target="_blank" class="block">
                  <img v-if="file.is_image" :src="file.url" :alt="file.name" class="w-24 h-24 object-cover rounded border border-gray-200 hover:opacity-75 transition-opacity" />
                  <span v-else class="inline-block px-2 py-1 text-xs text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100">
                    {{ file.name }}
                  </span>
                </a>
              </div>

              <div v-if="note.can_edit || note.can_delete" class="flex items-center gap-3 mt-2">
                <button v-if="note.can_edit" type="button" class="text-[11px] text-indigo-600 hover:underline" @click="startEdit(note)">Edytuj</button>
                <button v-if="note.can_delete" type="button" class="text-[11px] text-red-600 hover:underline" @click="remove(note)">Usuń</button>
              </div>
            </div>

            <div v-else>
              <mention-textarea v-model="editBody" :users="mentionableUsers" :rows="3" />
              <div class="flex items-center justify-end gap-2 mt-2">
                <button type="button" class="px-3 py-1 text-xs text-gray-500 hover:text-gray-700" @click="cancelEdit">Anuluj</button>
                <button type="button" class="btn-indigo px-3 py-1 text-xs" @click="saveEdit(note)">Zapisz</button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import LoadingButton from '@/Shared/LoadingButton'
import MentionTextarea from '@/Shared/MentionTextarea'
import ScreenshotUploader from '@/Shared/ScreenshotUploader'

export default {
  name: 'NotesSection',
  components: {
    LoadingButton,
    MentionTextarea,
    ScreenshotUploader,
  },
  props: {
    type: { type: String, required: true },
    notableId: { type: Number, required: true },
    notes: { type: Array, default: () => [] },
    mentionableUsers: { type: Array, default: () => [] },
    canComment: { type: Boolean, default: false },
  },
  data() {
    return {
      form: this.$inertia.form({
        type: this.type,
        notable_id: this.notableId,
        body: '',
        files: [],
      }),
      editingId: null,
      editBody: '',
    }
  },
  computed: {
    commentCount() {
      return this.notes.filter((note) => !note.system).length
    },
  },
  methods: {
    addFiles(files) {
      this.$refs.uploader?.addFiles(files)
    },
    submit() {
      if (!this.form.body.trim()) {
        return
      }

      this.form.post('/notes', {
        preserveScroll: true,
        onSuccess: () => this.form.reset('body', 'files'),
      })
    },
    startEdit(note) {
      this.editingId = note.id
      this.editBody = note.body
    },
    cancelEdit() {
      this.editingId = null
      this.editBody = ''
    },
    saveEdit(note) {
      if (!this.editBody.trim()) {
        return
      }

      this.$inertia.put(`/notes/${note.id}`, { body: this.editBody }, {
        preserveScroll: true,
        onSuccess: () => this.cancelEdit(),
      })
    },
    remove(note) {
      if (!confirm('Na pewno usunąć ten komentarz?')) {
        return
      }

      this.$inertia.delete(`/notes/${note.id}`, { preserveScroll: true })
    },
    /** Escapujemy treść, a potem podświetlamy same wzmianki. */
    renderBody(body) {
      const escaped = (body || '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        '\'': '&#39;',
      })[character])

      return escaped.replace(
        /@\[([^\]]+)\]\(user:(\d+)\)/g,
        '<span class="px-1 font-semibold text-indigo-700 bg-indigo-50 rounded">@$1</span>',
      )
    },
    initials(name) {
      return (name || '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
    },
  },
}
</script>
