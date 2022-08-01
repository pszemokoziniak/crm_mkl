<template>
  <div class="bg-white rounded-lg shadow overflow-auto grid flex py-2 px-6">
    <div>
      <span class="text-lg font-bold text-gray-800">July</span>
      <span class="ml-1 text-lg text-gray-600 font-normal">2022</span>
    </div>
    <div class="flex border-t border-l">
      <div v-for="day in days" :key="day" class="px-4 pt-2 border-r border-b relative" style="width: 222px; height: 120px;">
        <div class="inline-flex w-6 h-6 items-center justify-center cursor-pointer text-center leading-none rounded-full transition ease-in-out duration-100 text-gray-700 hover:bg-blue-200 text-gray-700 hover:bg-blue-200">{{ day }}</div>
        <div @click="showModal" class="overflow-y-auto mt-1" style="height: 80px;">
          7:00 - 15:00 <br>
          Czas pracy: 8
        </div>
      </div>
    </div>
  </div>

  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @close="open = false">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end sm:items-center justify-center min-h-full p-4 text-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900"> Wprowadź: </DialogTitle>
                    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
                      <fieldset :disabled="disabled == 0">
                        <form @submit.prevent="update">
                          <div class="flex flex-wrap -mb-8 -mr-6 p-8">
                            <text-input type="time" class="pb-8 pr-6 w-full lg:w-1/2" label="od" />
                            <text-input type="time" class="pb-8 pr-6 w-full lg:w-1/2" label="do" />
                          </div>
                        </form>
                      </fieldset>
                    </div>

                  </div>
                </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm" @click="open = false">Zapisz</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="open = false" ref="cancelButtonRef">Anuluj</button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

</template>

<script>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'

export default {
  components: {
    TextInput,
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
  },
  layout: Layout,
  data() {
    return {
      open: false, // default value for modal
      days: new Array(31).fill(0).map((x, i) => i+1)
    }
  },
  methods: {
    showModal() {
      this.open = true
    }
  }
}
</script>
