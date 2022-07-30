<template>
  <div class="bg-white rounded-lg shadow overflow-auto grid flex py-2 px-6">
    <div>
      <span class="text-lg font-bold text-gray-800">July</span>
      <span class="ml-1 text-lg text-gray-600 font-normal">2022</span>
    </div>
    <div class="flex border-t border-l">
      <div class="px-4 pt-2 border-r border-b relative" style="width: 222px; height: 120px;">
        <div class="inline-flex w-6 h-6 items-center justify-center cursor-pointer text-center leading-none rounded-full transition ease-in-out duration-100 text-gray-700 hover:bg-blue-200 text-gray-700 hover:bg-blue-200">3</div>
        <div class="overflow-y-auto mt-1" style="height: 80px;"></div>
      </div>
    </div>
  </div>
</template>

<script>
import Layout from '@/Shared/Layout'

export default {
  components: {},
  layout: Layout,
  props: {
    workers: Array,
  },
  data() {
    return {}
  },
}
</script>
