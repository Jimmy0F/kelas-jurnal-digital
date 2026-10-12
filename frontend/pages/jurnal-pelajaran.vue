<template>
  <div class="flex-1 flex flex-col">
    <AppHeader
      title="Jurnal Kegiatan"
      subtitle="Materi terbaru dan bahan ajar yang dipublikasikan oleh guru"
    />

    <main class="px-8 pb-8 space-y-6">
      <!-- Filter Bar -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 w-fit">
          <Calendar class="w-4 h-4 text-gray-400" />
          <span>Februari 2026</span>
        </div>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="search"
            type="text"
            placeholder="Cari materi..."
            class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
        </div>
      </div>

      <!-- Main Material Table -->
      <div class="bg-white rounded-2xl border border-gray-150 p-6 shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-gray-400 uppercase text-[10px] border-b border-gray-100">
                <th class="pb-3 font-semibold">Tanggal / Jam</th>
                <th class="pb-3 font-semibold">Instruktur</th>
                <th class="pb-3 font-semibold">Mata Pelajaran</th>
                <th class="pb-3 font-semibold">Bahasan & Rencana Belajar</th>
                <th class="pb-3 font-semibold text-center">Lampiran</th>
                <th class="pb-3 font-semibold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="item in filteredMaterials" :key="item.id" class="hover:bg-gray-50/50">
                <td class="py-4 font-bold text-gray-900 whitespace-nowrap">{{ item.date_time }}</td>
                <td class="py-4 text-gray-700 whitespace-nowrap">{{ item.teacher }}</td>
                <td class="py-4 font-bold text-gray-900 whitespace-nowrap">{{ item.subject }}</td>
                <td class="py-4 text-gray-600 max-w-xs">{{ item.topic }}</td>
                <td class="py-4 text-center">
                  <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ item.attachments_count }} file
                  </span>
                </td>
                <td class="py-4 text-right">
                  <button
                    type="button"
                    @click="selectedMaterial = item"
                    class="px-3 py-1.5 rounded-lg border border-amber-500/70 text-amber-700 bg-amber-50/30 hover:bg-amber-100 text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Baca Materi
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detail Modal Dialog -->
      <div v-if="selectedMaterial" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
              <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-primary">
                {{ selectedMaterial.subject }}
              </span>
              <h3 class="text-base font-bold text-gray-900 mt-1">{{ selectedMaterial.topic }}</h3>
            </div>
            <button @click="selectedMaterial = null" class="text-gray-400 hover:text-gray-600">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 text-xs text-gray-700">
            <div>
              <span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Rencana Belajar</span>
              <p class="bg-gray-50 p-3 rounded-xl border border-gray-100">{{ selectedMaterial.plan || 'Mempelajari arsitektur dan komponen aplikasi.' }}</p>
            </div>
            <div>
              <span class="font-bold text-gray-400 uppercase text-[10px] block mb-1">Catatan / Tugas Siswa</span>
              <p class="bg-gray-50 p-3 rounded-xl border border-gray-100">{{ selectedMaterial.assignment || 'Tugas mandiri sesuai materi pelajaran.' }}</p>
            </div>
            <div class="pt-2">
              <span class="font-bold text-gray-400 uppercase text-[10px] block mb-2">Lampiran Dokumentasi</span>
              <div class="flex items-center gap-2">
                <a href="#" class="inline-flex items-center gap-2 px-3 py-2 bg-red-50 text-primary hover:bg-red-100 rounded-xl font-semibold border border-red-200">
                  <Download class="w-4 h-4" />
                  <span>Modul_Belajar_Kelas.pdf</span>
                </a>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button
              @click="selectedMaterial = null"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold text-xs cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Calendar, Search, X, Download } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const search = ref('')
const materials = ref<any[]>([])
const selectedMaterial = ref<any>(null)

const filteredMaterials = computed(() => {
  if (!search.value) return materials.value
  const q = search.value.toLowerCase()
  return materials.value.filter(m => m.topic.toLowerCase().includes(q) || m.subject.toLowerCase().includes(q) || m.teacher.toLowerCase().includes(q))
})

onMounted(async () => {
  try {
    const res = await $fetch<any[]>(`${authStore.apiBase}/journals/material`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    materials.value = res
  } catch (e) {
    console.error(e)
  }
})
</script>
