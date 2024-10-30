<template>
  <div>
    <Head title="Budowy" />
    <h1 class="mb-8 text-3xl font-bold">Budowy KCP</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null" />
          <option value="my">Moje budowy</option>
          <option value="with">Wszystkie budowy</option>
          <option value="only">Usunięte budowy</option>
        </select>
      </search-filter>
<!--      <Link class="btn-indigo" href="/budowy/create">-->
<!--        <span>Utwórz</span>-->
<!--        <span class="hidden md:inline">&nbsp;Budowę</span>-->
<!--      </Link>-->
    </div>
    {{workers_count}}
    <div v-if="user_owner[1]===3" class="my-3 font-bold mb-3">Twoje budowy</div>
    <div v-if="user_owner[1]===3" class="bg-white rounded-md shadow overflow-x-auto my-3">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
            <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in organizations_user" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${item.id}/edit`">
                {{ item.nazwaBud }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                {{ item.workers_count }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <div v-if="item.inzynier">
                  {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <div v-if="item.country">
                  {{ item.country.name }}
                </div>
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
          <tr v-if="organizations_user.length === 0">
            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="user_owner[1]===3" class="py-3 font-bold">Inne budowy</div>
    <div v-if="user_owner[1]===3" class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
          <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="item in organizations_other" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" href="#">
              {{ item.nazwaBud }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" href="#" tabindex="-1">
              {{ item.workers_count }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" href="#" tabindex="-1">
              <div v-if="item.inzynier">
                {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
              </div>
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" href="#" tabindex="-1">
              <div v-if="item.country">
                {{ item.country.name }}
              </div>
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" href="#" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="organizations_other.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
        </tr>
        </tbody>
      </table>
    </div>
    <div v-if="user_owner[1]===1 || user_owner[1]===2" class="py-3 font-bold">Wszystkie budowy</div>
    <div v-if="user_owner[1]===1 || user_owner[1]===2" class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
          <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="item in organizations_biuro" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link v-if="user_owner[1] === 3 && (user_owner[3] === item.kierownikBud_id || user_owner[3] === item.inzynier_id) && item.deleted_at === null" class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${item.id}/edit`">
              {{ item.nazwaBud }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
            <Link v-if="user_owner[1] === 1 || user_owner[1] === 2" class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${item.id}/edit`">
              {{ item.nazwaBud }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
            <Link v-if="user_owner[1] === 3 && (user_owner[3] !== item.kierownikBud_id || user_owner[3] !== item.inzynier_id) && item.deleted_at !== null" class="flex items-center px-6 py-4 focus:text-indigo-500" href="#">
              {{ item.nazwaBud }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link v-if="user_owner[1] === 3 && (user_owner[3] === item.kierownikBud_id || user_owner[3] === item.inzynier_id) && item.deleted_at === null" class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              {{ item.workers_count }}
            </Link>
            <Link v-if="user_owner[1] === 1 || user_owner[1] === 2" class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              {{ item.workers_count }}
            </Link>
            <Link v-if="user_owner[1] === 3 && (user_owner[3] !== item.kierownikBud_id || user_owner[3] !== item.inzynier_id) && item.deleted_at !== null" class="flex items-center px-6 py-4" href="#" tabindex="-1">
              {{ item.workers_count }}
            </Link>
          </td>
          <td class="border-t">
            <Link v-if="user_owner[1] === 3 && ((user_owner[3] === item.kierownikBud_id || user_owner[3] === item.inzynier_id) || user_owner[3] === item.inzynier_id) && item.deleted_at === null" class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              <div v-if="item.inzynier">
                {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
              </div>
            </Link>
            <Link v-if="user_owner[1] === 1 || user_owner[1] === 2" class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              <div v-if="item.inzynier">
                {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
              </div>
            </Link>
            <Link v-if="user_owner[1] === 3 && (user_owner[0] !== item.kierownikBud_id || user_owner[0] !== item.inzynier_id) && item.deleted_at !== null" class="flex items-center px-6 py-4" href="#" tabindex="-1">
              <div v-if="item.inzynier">
                {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
              </div>
            </Link>
          </td>
          <td class="border-t">
            <Link v-if="user_owner[1] === 3 && ((user_owner[0] === item.kierownikBud_id || user_owner[0] === item.inzynier_id) || user_owner[3] === item.inzynier_id) && item.deleted_at === null" class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              <div v-if="item.country">
                {{ item.country.name }}
              </div>
            </Link>
            <Link v-if="user_owner[1] === 1 || user_owner[1] === 2" class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              <div v-if="item.country">
                {{ item.country.name }}
              </div>
            </Link>
            <Link v-if="user_owner[1] === 3 && (user_owner[0] !== item.kierownikBud_id || user_owner[0] !== item.inzynier_id) && item.deleted_at !== null" class="flex items-center px-6 py-4" href="#" tabindex="-1">
              <div v-if="item.country">
                {{ item.country.name }}
              </div>
            </Link>
          </td>
          <td class="w-px border-t">
            <Link v-if="user_owner[1] === 3 && ((user_owner[3] === item.kierownikBud_id || user_owner[3] === item.inzynier_id) || user_owner[3] === item.inzynier_id) && item.deleted_at === null" class="flex items-center px-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
            <Link v-if="user_owner[1] === 1 || user_owner[1] === 2" class="flex items-center px-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
            <Link v-if="user_owner[1] === 3 && ((user_owner[3] !== item.kierownikBud_id || user_owner[3] !== item.inzynier_id) || user_owner[3] !== item.inzynier_id) && item.deleted_at !== null" class="flex items-center px-4" href="#" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="organizations_biuro.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
        </tr>
        </tbody>
      </table>
    </div>
<!--    <pagination class="mt-6" :links="organizations.links" />-->
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
// import Pagination from '@/Shared/Pagination'
import SearchFilter from '@/Shared/SearchFilter'

export default {
  components: {
    Head,
    Icon,
    Link,
    // Pagination,
    SearchFilter,
  },
  layout: Layout,
  props: {
    filters: Object,
    organizations_user: Object,
    organizations_other: Object,
    organizations_biuro: Object,
    buildings: Object,
    inzynier: Object,
    user_owner: Array,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/', pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
