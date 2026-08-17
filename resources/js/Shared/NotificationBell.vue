<template>
  <dropdown placement="bottom-end">
    <template #default>
      <div class="group relative flex items-center cursor-pointer select-none p-1">
        <icon name="bell" class="w-5 h-5 fill-gray-600 group-hover:fill-indigo-600" />
        <span
          v-if="unread > 0"
          class="absolute -top-1 -right-1 flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full"
        >
          {{ unread > 9 ? '9+' : unread }}
        </span>
      </div>
    </template>
    <template #dropdown>
      <div class="mt-2 w-80 bg-white rounded shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b">
          <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Powiadomienia</span>
          <button v-if="unread > 0" type="button" class="text-[10px] text-indigo-600 hover:underline" @click="readAll">
            Oznacz wszystkie
          </button>
        </div>
        <div v-if="items.length === 0" class="px-4 py-6 text-center text-xs text-gray-400 italic">
          Brak powiadomień
        </div>
        <div v-else class="max-h-80 overflow-y-auto divide-y divide-gray-50">
          <button
            v-for="item in items"
            :key="item.id"
            type="button"
            class="w-full text-left px-4 py-3 hover:bg-indigo-50 transition-colors"
            :class="{ 'bg-indigo-50/40': !item.read }"
            @click="open(item)"
          >
            <div class="flex items-start gap-2">
              <span class="mt-1 w-1.5 h-1.5 rounded-full flex-shrink-0" :class="item.read ? 'bg-transparent' : 'bg-indigo-500'" />
              <div class="min-w-0">
                <div class="text-xs font-bold text-gray-800 truncate">{{ item.subject }}</div>
                <div class="text-xs text-gray-600 truncate">
                  <span class="font-medium">{{ item.author }}</span>: {{ item.excerpt }}
                </div>
                <div class="text-[10px] text-gray-400 mt-0.5">{{ item.created_at }}</div>
              </div>
            </div>
          </button>
        </div>
      </div>
    </template>
  </dropdown>
</template>

<script>
import Dropdown from '@/Shared/Dropdown'
import Icon from '@/Shared/Icon'

export default {
  components: {
    Dropdown,
    Icon,
  },
  computed: {
    unread() {
      return this.$page.props.notifications?.unread ?? 0
    },
    items() {
      return this.$page.props.notifications?.items ?? []
    },
  },
  methods: {
    open(item) {
      this.$inertia.post(`/notifications/${item.id}/read`)
    },
    readAll() {
      this.$inertia.post('/notifications/read-all', {}, { preserveScroll: true })
    },
  },
}
</script>
