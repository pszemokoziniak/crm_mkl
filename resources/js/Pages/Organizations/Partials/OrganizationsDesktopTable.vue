<template>
  <div class="hidden overflow-x-auto sm:block">
    <table class="w-full whitespace-nowrap">
      <thead>
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Kraj</th>
          <th class="hidden pb-4 pt-6 px-6 border-t lg:table-cell">Kierownicy</th>
          <th class="hidden pb-4 pt-6 px-6 border-t lg:table-cell">Inżynierowie</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Aktywna</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="organization in organizations" :key="organization.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/edit`">
              {{ organization.nazwaBud }}
              <Icon v-if="organization.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>

          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div v-if="organization.country">{{ organization.country.name }}</div>
            </Link>
          </td>

          <td class="hidden border-t lg:table-cell">
            <Link
              class="flex items-center px-6 py-4"
              :href="`/budowy/${organization.id}/edit`"
              tabindex="-1"
            >
              <div
                v-if="organization.kierownicy"
                class="max-w-[320px]"
                :title="organization.kierownicy"
              >
                <p
                  v-for="(name, idx) in organization.kierownicy.split(', ')"
                  :key="idx"
                  class="text-xs leading-tight text-gray-700"
                >
                  {{ name }}
                </p>
              </div>
            </Link>
          </td>

          <td class="hidden border-t lg:table-cell">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <div v-if="organization.inzynierowie?.length" class="max-w-[320px]">
                <p v-for="(name, idx) in organization.inzynierowie" :key="idx" class="text-gray-700 text-xs leading-tight">
                  {{ name }}
                </p>
              </div>
            </Link>
          </td>

          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded" :class="organization.has_active_workers ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                {{ organization.has_active_workers ? 'TAK' : 'NIE' }}
              </span>
            </Link>
          </td>

          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
              <Icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>

        <tr v-if="organizations.length === 0">
          <td class="px-6 py-4 border-t" colspan="6">Brak danych.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'

export default {
  components: { Link, Icon },
  props: {
    organizations: { type: Array, required: true },
  },
}
</script>
