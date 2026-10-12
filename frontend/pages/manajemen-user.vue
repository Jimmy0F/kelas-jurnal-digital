<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Manajemen Pengguna & Hak Akses</h1>
        <p class="text-sm text-gray-500 mt-1">
          Kelola akun guru, sekretaris, dan siswa beserta matriks kewenangan sistem
        </p>
      </div>

      <!-- Tab Switcher -->
      <div class="flex bg-gray-100 p-1 rounded-xl self-start md:self-auto">
        <button
          @click="activeTab = 'users'"
          :class="[
            'px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5',
            activeTab === 'users' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'
          ]"
        >
          <UsersIcon class="w-4 h-4 text-brand-red" />
          Daftar Pengguna
        </button>
        <button
          @click="activeTab = 'roles'"
          :class="[
            'px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center gap-1.5',
            activeTab === 'roles' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'
          ]"
        >
          <ShieldCheckIcon class="w-4 h-4 text-brand-red" />
          Matriks Hak Akses
        </button>
      </div>
    </div>

    <!-- Alert / Toast -->
    <div v-if="successMsg" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center justify-between">
      <div class="flex items-center gap-2 text-sm font-medium">
        <CheckCircle2Icon class="w-5 h-5 text-green-600" />
        {{ successMsg }}
      </div>
      <button @click="successMsg = ''" class="text-green-500 hover:text-green-700 text-sm">✕</button>
    </div>

    <!-- TAB 1: DAFTAR PENGGUNA -->
    <div v-if="activeTab === 'users'" class="space-y-4">
      <!-- Action Bar & Filter -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-4 md:p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <div>
            <select
              v-model="roleFilter"
              @change="fetchUsers"
              class="px-3.5 py-2 rounded-xl border border-gray-200 text-xs font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red bg-white"
            >
              <option value="Semua Peran">Semua Peran</option>
              <option value="SISWA">Siswa</option>
              <option value="SEKRETARIS">Sekretaris</option>
              <option value="GURU_PENGAJAR">Guru Pengajar</option>
              <option value="GURU_PEMBIMBING">Guru Pembimbing</option>
            </select>
          </div>

          <div class="relative w-64">
            <SearchIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              @input="debounceSearch"
              type="text"
              placeholder="Cari nama, email, NIP/NIS..."
              class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red"
            />
          </div>
        </div>

        <button
          @click="showCreateModal = true"
          class="px-4 py-2.5 rounded-xl bg-brand-red hover:bg-brand-red-dark text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-sm"
        >
          <UserPlusIcon class="w-4 h-4" />
          Tambah Pengguna Baru
        </button>
      </div>

      <!-- Table of Users -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-base font-bold text-gray-800">Daftar Akun Pengguna</h2>
          <span class="text-xs text-gray-500 font-medium">Total: {{ users.length }} Pengguna</span>
        </div>

        <div v-if="loadingUsers" class="py-16 text-center text-gray-400 text-sm">
          Memuat data pengguna...
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-semibold">
                <th class="py-3 px-4">Nama & Email</th>
                <th class="py-3 px-4">Peran</th>
                <th class="py-3 px-4">NIP / NIS</th>
                <th class="py-3 px-4">Kelas</th>
                <th class="py-3 px-4 text-center">Status Akun</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="u in users"
                :key="u.id"
                class="hover:bg-gray-50/50 transition"
              >
                <td class="py-3.5 px-4">
                  <div class="font-bold text-gray-900 text-sm">{{ u.name }}</div>
                  <div class="text-[11px] text-gray-400">{{ u.email }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                    :class="{
                      'bg-purple-50 text-purple-700': u.role === 'GURU_PEMBIMBING',
                      'bg-blue-50 text-blue-700': u.role === 'GURU_PENGAJAR',
                      'bg-amber-50 text-amber-700': u.role === 'SEKRETARIS',
                      'bg-emerald-50 text-emerald-700': u.role === 'SISWA',
                    }"
                  >
                    {{ u.role }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-mono text-gray-600">
                  {{ u.nip_nis || '-' }}
                </td>
                <td class="py-3.5 px-4 text-gray-700">
                  {{ u.school_class?.name || (u.role.includes('GURU') ? 'Semua Kelas' : '-') }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <button
                    @click="toggleUserActive(u)"
                    :class="[
                      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition',
                      u.is_active
                        ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100'
                        : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100'
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-green-500' : 'bg-red-500'"></span>
                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                  </button>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <button
                    @click="resetUserPassword(u)"
                    class="px-2.5 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 text-[11px] font-semibold transition"
                    title="Reset ke sandi awal"
                  >
                    Reset Sandi
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: MATRIKS HAK AKSES -->
    <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-card p-5 md:p-6 space-y-4">
      <div>
        <h2 class="text-base font-bold text-gray-800">Matriks Hak Akses Peran (RBAC)</h2>
        <p class="text-xs text-gray-400 mt-0.5">Pemetaan hak akses sistem berdasarkan Bagian 5 spesifikasi sistem jurnal</p>
      </div>

      <div class="overflow-x-auto border border-gray-100 rounded-xl">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider">
              <th class="py-3.5 px-4">Fitur / Menu Sistem</th>
              <th class="py-3.5 px-4 text-center">Siswa</th>
              <th class="py-3.5 px-4 text-center">Sekretaris</th>
              <th class="py-3.5 px-4 text-center">Guru Pengajar</th>
              <th class="py-3.5 px-4 text-center">Guru Pembimbing</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="rule in rbacMatrix" :key="rule.feature" class="hover:bg-gray-50/50">
              <td class="py-3 px-4 font-semibold text-gray-800">{{ rule.feature }}</td>
              <td class="py-3 px-4 text-center font-bold" :class="rule.siswa ? 'text-green-600' : 'text-gray-300'">
                {{ rule.siswa ? '✓' : '—' }}
              </td>
              <td class="py-3 px-4 text-center font-bold" :class="rule.sekretaris ? 'text-green-600' : 'text-gray-300'">
                {{ rule.sekretaris ? '✓' : '—' }}
              </td>
              <td class="py-3 px-4 text-center font-bold" :class="rule.pengajar ? 'text-green-600' : 'text-gray-300'">
                {{ rule.pengajar ? '✓' : '—' }}
              </td>
              <td class="py-3 px-4 text-center font-bold" :class="rule.pembimbing ? 'text-green-600' : 'text-gray-300'">
                {{ rule.pembimbing ? '✓' : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tambah Pengguna Baru -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-base font-bold text-gray-900">Tambah Pengguna Baru</h3>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitCreateUser" class="space-y-3.5">
          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
              Nama Lengkap
            </label>
            <input
              v-model="newUser.name"
              type="text"
              placeholder="Contoh: Rina Safitri"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
              Peran Akun
            </label>
            <select
              v-model="newUser.role"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red bg-white"
              required
            >
              <option value="SISWA">Siswa</option>
              <option value="SEKRETARIS">Sekretaris Kelas</option>
              <option value="GURU_PENGAJAR">Guru Pengajar</option>
              <option value="GURU_PEMBIMBING">Guru Pembimbing</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
              Email Pengguna
            </label>
            <input
              v-model="newUser.email"
              type="email"
              placeholder="email@sekolah.sch.id"
              class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red"
              required
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                NIP / NIS
              </label>
              <input
                v-model="newUser.nip_nis"
                type="text"
                placeholder="1029384"
                class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                Kelas
              </label>
              <select
                v-model="newUser.class_id"
                class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red bg-white"
              >
                <option :value="null">-- Tidak Terikat --</option>
                <option value="1">XI RPL 1</option>
              </select>
            </div>
          </div>

          <p class="text-[11px] text-gray-400">
            * Kata sandi awal otomatis diatur ke: <strong>password123</strong>. Pengguna dapat mengubahnya setelah login.
          </p>

          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-4 py-2 rounded-xl bg-brand-red text-white text-xs font-semibold hover:bg-brand-red-dark transition disabled:opacity-50"
            >
              {{ creating ? 'Menyimpan...' : 'Simpan Pengguna' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Users as UsersIcon,
  ShieldCheck as ShieldCheckIcon,
  UserPlus as UserPlusIcon,
  Search as SearchIcon,
  CheckCircle2Icon
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()

const activeTab = ref<'users' | 'roles'>('users')
const loadingUsers = ref(false)
const users = ref<any[]>([])
const roleFilter = ref('Semua Peran')
const searchQuery = ref('')
const successMsg = ref('')

const showCreateModal = ref(false)
const creating = ref(false)
const newUser = ref({
  name: '',
  role: 'SISWA',
  email: '',
  nip_nis: '',
  class_id: '1'
})

let searchTimeout: any = null
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUsers()
  }, 350)
}

const rbacMatrix = [
  { feature: 'Dashboard Statistik Personal', siswa: true, sekretaris: true, pengajar: true, pembimbing: true },
  { feature: 'Melihat Jurnal Pelajaran & Materi', siswa: true, sekretaris: true, pengajar: true, pembimbing: true },
  { feature: 'Melihat Riwayat Presensi Sendiri', siswa: true, sekretaris: true, pengajar: false, pembimbing: false },
  { feature: 'Input Presensi Kelas & Kunci PIN', siswa: false, sekretaris: true, pengajar: false, pembimbing: false },
  { feature: 'Input Jurnal Harian Kelas', siswa: false, sekretaris: true, pengajar: false, pembimbing: false },
  { feature: 'Pengajuan Surat Izin Siswa', siswa: false, sekretaris: true, pengajar: false, pembimbing: false },
  { feature: 'Konfirmasi Jurnal Kelas (Guru)', siswa: false, sekretaris: false, pengajar: true, pembimbing: false },
  { feature: 'Kelola Rencana & Materi Belajar', siswa: false, sekretaris: false, pengajar: true, pembimbing: false },
  { feature: 'Persetujuan / Tolak Surat Izin', siswa: false, sekretaris: false, pengajar: false, pembimbing: true },
  { feature: 'Manajemen Akun & Reset Sandi', siswa: false, sekretaris: false, pengajar: false, pembimbing: true },
  { feature: 'Laporan Rekapitulasi Presensi Semester', siswa: false, sekretaris: false, pengajar: true, pembimbing: true },
  { feature: 'Audit Log & Pelacakan Sistem', siswa: false, sekretaris: false, pengajar: false, pembimbing: true },
]

const fetchUsers = async () => {
  loadingUsers.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
      params: {
        role: roleFilter.value,
        search: searchQuery.value
      }
    })
    users.value = res.data || res || []
  } catch (err: any) {
    console.error('Error fetching users:', err)
  } finally {
    loadingUsers.value = false
  }
}

const toggleUserActive = async (u: any) => {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/users/${u.id}/toggle-active`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    u.is_active = res.is_active
    successMsg.value = `Status akun ${u.name} berhasil diubah.`
  } catch (err: any) {
    alert(err?.data?.message || 'Gagal mengubah status pengguna')
  }
}

const resetUserPassword = async (u: any) => {
  if (!confirm(`Reset kata sandi untuk ${u.name} ke default 'password123'?`)) return
  try {
    await $fetch(`${config.public.apiBase}/users/${u.id}/reset-password`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    successMsg.value = `Kata sandi ${u.name} berhasil direset ke password123.`
  } catch (err: any) {
    alert(err?.data?.message || 'Gagal mereset sandi')
  }
}

const submitCreateUser = async () => {
  creating.value = true
  successMsg.value = ''
  try {
    await $fetch(`${config.public.apiBase}/users`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: newUser.value
    })
    successMsg.value = 'Pengguna baru berhasil ditambahkan!'
    showCreateModal.value = false
    newUser.value = { name: '', role: 'SISWA', email: '', nip_nis: '', class_id: '1' }
    await fetchUsers()
  } catch (err: any) {
    alert(err?.data?.message || 'Gagal menambahkan pengguna')
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  fetchUsers()
})
</script>
