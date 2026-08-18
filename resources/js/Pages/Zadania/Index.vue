<template>
  <div>
    <Head title="Zadania" />
    <h1 class="mb-8 text-3xl font-bold text-gray-900">Zadania</h1>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <search-filter v-model="form.search" class="w-full max-w-md" @reset="reset">
        <!-- Na kanbanie status wynika z kolumny, więc filtrujemy nim tylko listę. -->
        <template v-if="form.view === 'lista'">
          <label class="block text-gray-700">Status:</label>
          <select v-model="form.status" class="form-select mt-1 w-full">
            <option :value="null">Wszystkie</option>
            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
          </select>
        </template>
        <label class="block mt-4 text-gray-700">Priorytet:</label>
        <select v-model="form.priority" class="form-select mt-1 w-full">
          <option :value="null">Wszystkie</option>
          <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
        </select>
        <label class="block mt-4 text-gray-700">Osoba przypisana:</label>
        <select v-model="form.assignee" class="form-select mt-1 w-full">
          <option :value="null">Wszyscy</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
        </select>
        <label class="block mt-4 text-gray-700">Archiwum:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null">Bez zarchiwizowanych</option>
          <option value="with">Ze zarchiwizowanymi</option>
          <option value="only">Tylko zarchiwizowane</option>
        </select>
      </search-filter>

      <div class="flex items-center gap-3">
        <div class="flex bg-white rounded shadow overflow-hidden">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium"
            :class="form.view === 'kanban' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-50'"
            @click="form.view = 'kanban'"
          >
            Kanban
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium"
            :class="form.view === 'lista' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-50'"
            @click="form.view = 'lista'"
          >
            Lista
          </button>
        </div>
        <Link class="btn-indigo" href="/zadania/create">Nowe zgłoszenie</Link>
      </div>
    </div>

    <!-- KANBAN -->
    <div v-if="form.view === 'kanban'" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="column in columns"
        :key="column.value"
        class="flex flex-col bg-gray-100 rounded-md"
        :class="{ 'ring-2 ring-indigo-400': dragOverColumn === column.value }"
        @dragover.prevent="dragOverColumn = column.value"
        @dragleave="dragOverColumn === column.value && (dragOverColumn = null)"
        @drop="drop(column)"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" :class="statusDot(column.value)" />
            <span class="text-sm font-bold text-gray-700">{{ column.label }}</span>
          </div>
          <span class="px-2 py-0.5 text-xs font-medium text-gray-600 bg-white rounded-full">{{ column.count }}</span>
        </div>

        <div class="flex-1 p-3 space-y-3 min-h-[120px]">
          <div
            v-for="item in column.items"
            :key="item.id"
            class="bg-white rounded shadow-sm border border-gray-100 p-3 cursor-move hover:shadow transition-shadow"
            draggable="true"
            @dragstart="dragStart(item)"
            @dragend="dragEnd"
          >
            <div class="flex items-start justify-between gap-2">
              <Link :href="`/zadania/${item.id}`" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                {{ item.title }}
              </Link>
              <span class="px-1.5 py-0.5 text-[10px] font-bold rounded flex-shrink-0" :class="priorityClass(item.priority)">
                {{ priorityLabel(item.priority) }}
              </span>
            </div>

            <a v-if="item.url" :href="item.url" target="_blank" class="block mt-1 text-[11px] text-indigo-500 truncate hover:underline" @click.stop>
              {{ item.url }}
            </a>

            <div class="flex items-center justify-between mt-3 text-[11px] text-gray-500">
              <span class="truncate">{{ item.assignee ? item.assignee.name : 'nieprzypisane' }}</span>
              <span class="flex items-center gap-2 flex-shrink-0">
                <span v-if="item.screenshots_count > 0" title="Print screeny">🖼 {{ item.screenshots_count }}</span>
                <span v-if="item.notes_count > 0" title="Komentarze">💬 {{ item.notes_count }}</span>
                <icon v-if="item.deleted_at" name="trash" class="w-3 h-3 fill-gray-400" />
              </span>
            </div>

            <div v-if="item.deadline" class="mt-1 text-[11px]" :class="overdue(item) ? 'text-red-600 font-medium' : 'text-gray-400'">
              termin: {{ item.deadline }}
            </div>
          </div>

          <p v-if="column.items.length === 0" class="py-6 text-center text-xs text-gray-400 italic">
            Przeciągnij tutaj zgłoszenie
          </p>
        </div>
      </div>
    </div>

    <!-- LISTA -->
    <div v-else>
      <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="text-left font-bold bg-gray-50 border-b border-gray-100">
              <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Tytuł</th>
              <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Status</th>
              <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Priorytet</th>
              <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Przypisane</th>
              <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Zgłosił</th>
              <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Termin</th>
              <th class="py-4 px-6" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in zadania.data" :key="item.id" class="group hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <Link :href="`/zadania/${item.id}`" class="flex items-center font-medium text-gray-900 focus:text-indigo-500">
                  {{ item.title }}
                  <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
                </Link>
                <span v-if="item.url" class="block text-[11px] text-indigo-500 truncate max-w-xs">{{ item.url }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full border" :class="statusClass(item.status)">
                  {{ item.status_label }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded" :class="priorityClass(item.priority)">
                  {{ priorityLabel(item.priority) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ item.assignee ? item.assignee.name : '-' }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ item.reporter ? item.reporter.name : '-' }}</td>
              <td class="px-6 py-4 text-sm" :class="overdue(item) ? 'text-red-600 font-medium' : 'text-gray-600'">
                {{ item.deadline || '-' }}
              </td>
              <td class="px-6 py-4 text-right">
                <Link :href="`/zadania/${item.id}`" class="text-gray-400 group-hover:text-indigo-600 transition-colors">
                  <icon name="cheveron-right" class="w-6 h-6 fill-current" />
                </Link>
              </td>
            </tr>
            <tr v-if="zadania.data.length === 0">
              <td class="px-6 py-12 text-center text-gray-500" colspan="7">
                <div class="flex flex-col items-center">
                  <icon name="zadania" class="mb-2 w-12 h-12 fill-gray-200" />
                  <p>Brak zgłoszeń</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <pagination class="mt-6" :links="zadania.links" />
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import Pagination from '@/Shared/Pagination'
import SearchFilter from '@/Shared/SearchFilter'
import mapValues from 'lodash/mapValues'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: {
    Head,
    Icon,
    Link,
    Pagination,
    SearchFilter,
  },
  layout: Layout,
  props: {
    filters: Object,
    statuses: Array,
    priorities: Array,
    users: Array,
    columns: { type: Array, default: () => [] },
    zadania: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        status: this.filters.status || null,
        priority: this.filters.priority || null,
        assignee: this.filters.assignee || null,
        trashed: this.filters.trashed || null,
        view: this.filters.view,
      },
      dragged: null,
      dragOverColumn: null,
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/zadania', pickBy(this.form), { preserveState: true, replace: true })
      }, 150),
    },
    // Kanban pokazuje wszystkie statusy — filtr statusu wyzerowałby kolumny.
    'form.view'(view) {
      if (view === 'kanban') {
        this.form.status = null
      }
    },
  },
  methods: {
    reset() {
      this.form = { ...mapValues(this.form, () => null), view: this.form.view }
    },
    dragStart(item) {
      this.dragged = item
    },
    dragEnd() {
      this.dragged = null
      this.dragOverColumn = null
    },
    drop(column) {
      const item = this.dragged
      this.dragEnd()

      if (!item || item.status === column.value) {
        return
      }

      this.$inertia.put(`/zadania/${item.id}/status`, { status: column.value }, { preserveScroll: true })
    },
    statusDot(status) {
      return {
        do_zrobienia: 'bg-gray-400',
        w_toku: 'bg-indigo-500',
        test: 'bg-yellow-500',
        zrobione: 'bg-green-500',
      }[status] || 'bg-gray-400'
    },
    statusClass(status) {
      return {
        do_zrobienia: 'bg-gray-100 text-gray-800 border-gray-200',
        w_toku: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        test: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        zrobione: 'bg-green-100 text-green-800 border-green-200',
      }[status] || 'bg-gray-100 text-gray-800 border-gray-200'
    },
    priorityClass(priority) {
      return {
        wysoki: 'bg-red-100 text-red-700',
        normalny: 'bg-gray-100 text-gray-600',
        niski: 'bg-gray-50 text-gray-400',
      }[priority] || 'bg-gray-100 text-gray-600'
    },
    priorityLabel(priority) {
      return this.priorities.find((item) => item.value === priority)?.label || priority
    },
    overdue(item) {
      return item.deadline && item.status !== 'zrobione' && item.deadline < new Date().toISOString().substr(0, 10)
    },
  },
}
</script>
