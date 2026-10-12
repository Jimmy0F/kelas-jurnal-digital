<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Pusat Notifikasi & Log Aktivitas</h1>
        <p class="text-sm text-gray-500 mt-1">
          Pantau update terkini, pemberitahuan persetujuan, dan catatan riwayat audit sistem
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="activeTab !== 'audit'"
          @click="markAllRead"
          class="px-3.5 py-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition flex items-center gap-1.5 shadow-xs"
        >
          <CheckCheckIcon class="w-4 h-4 text-green-600" />
          Tandai Semua Dibaca
        </button>
      </div>
    </div>

    <!-- Filter Pills / Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 pb-3">
      <button
        @click="switchFilter('ALL')"
        :class="[
          'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
          activeTab === 'notifications' && filterType === 'ALL'
            ? 'bg-brand-red text-white shadow-xs'
            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
        ]"
      >
        Semua Notifikasi
      </button>

      <button
        @click="switchFilter('UNREAD')"
        :class="[
          'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
          activeTab === 'notifications' && filterType === 'UNREAD'
            ? 'bg-brand-red text-white shadow-xs'
            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
        ]"
      >
        Belum Dibaca
        <span
          v-if="unreadCount > 0"
          :class="[
            'px-1.5 py-0.2 rounded-full text-[10px] font-bold',
            activeTab === 'notifications' && filterType === 'UNREAD' ? 'bg-white text-brand-red' : 'bg-red-100 text-brand-red'
          ]"
        >
          {{ unreadCount }}
        </span>
      </button>

      <button
        @click="switchFilter('JURNAL')"
        :class="[
          'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
          activeTab === 'notifications' && filterType === 'JURNAL'
            ? 'bg-brand-red text-white shadow-xs'
            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
        ]"
      >
        Jurnal
      </button>

      <button
        @click="switchFilter('SURAT_IZIN')"
        :class="[
          'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5',
          activeTab === 'notifications' && filterType === 'SURAT_IZIN'
            ? 'bg-brand-red text-white shadow-xs'
            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
        ]"
      >
        Surat Izin
      </button>

      <button
        @click="switchToAudit"
        :class="[
          'px-4 py-2 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 ml-auto',
          activeTab === 'audit'
            ? 'bg-gray-900 text-white shadow-xs'
            : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'
        ]"
      >
        <ShieldAlertIcon class="w-4 h-4 text-amber-500" />
        Log Audit Aktivitas
      </button>
    </div>

    <!-- NOTIFIKASI LIST -->
    <div v-if="activeTab === 'notifications'" class="space-y-3">
      <div v-if="loading" class="bg-white rounded-2xl border border-gray-100 shadow-card py-16 text-center text-gray-400 text-sm">
        Memuat notifikasi...
      </div>

      <div v-else-if="notifications.length === 0" class="bg-white rounded-2xl border border-gray-100 shadow-card py-16 text-center text-gray-400 text-sm">
        <BellOffIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
        Tidak ada notifikasi dalam kategori ini.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="item in notifications"
          :key="item.id"
          @click="markOneRead(item)"
          class="p-4 rounded-2xl border transition flex items-start gap-3.5 cursor-pointer"
          :class="[
            item.is_read
              ? 'bg-white border-gray-100 hover:border-gray-200'
              : 'bg-red-50/20 border-red-200/70 shadow-xs'
          ]"
        >
          <div
            class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
            :class="[
              item.type === 'JURNAL'
                ? 'bg-blue-50 text-blue-600'
                : item.type === 'SURAT_IZIN'
                  ? 'bg-amber-50 text-amber-600'
                  : 'bg-red-50 text-brand-red'
            ]"
          >
            <BookOpenIcon v-if="item.type === 'JURNAL'" class="w-5 h-5" />
            <MailIcon v-else-if="item.type === 'SURAT_IZIN'" class="w-5 h-5" />
            <BellIcon v-else class="w-5 h-5" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h3 class="text-sm font-bold text-gray-900 truncate">
                {{ item.title }}
              </h3>
              <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] text-gray-400">{{ item.time_ago }}</span>
                <span v-if="!item.is_read" class="w-2 h-2 rounded-full bg-brand-red"></span>
              </div>
            </div>
            <p class="text-xs text-gray-600 mt-1">
              {{ item.body }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- LOG AUDIT LIST (MOCKUP 31) -->
    <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-gray-800">Catatan Jejak Audit Sistem</h2>
          <p class="text-xs text-gray-400 mt-0.5">Riwayat aktivitas pengguna, perubahan status presensi, dan konfirmasi</p>
        </div>
        <button
          @click="fetchAuditLogs"
          class="text-xs text-brand-red hover:underline font-semibold flex items-center gap-1"
        >
          <RefreshCwIcon class="w-3.5 h-3.5" /> Segarkan Log
        </button>
      </div>

      <div v-if="loadingAudit" class="py-16 text-center text-gray-400 text-sm">
        Memuat log audit...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-semibold">
              <th class="py-3 px-4 w-28">Waktu</th>
              <th class="py-3 px-4">Pengguna</th>
              <th class="py-3 px-4">Aktivitas / Operasi</th>
              <th class="py-3 px-4 font-mono">Alamat IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="log in auditLogs"
              :key="log.id"
              class="hover:bg-gray-50/50 transition"
            >
              <td class="py-3.5 px-4 font-medium text-gray-500 whitespace-nowrap">{{ log.time }}</td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-gray-900">{{ log.user }}</span>
              </td>
              <td class="py-3.5 px-4 text-gray-700 font-medium">
                {{ log.action }}
              </td>
              <td class="py-3.5 px-4 text-gray-400 font-mono">{{ log.ip_address }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Bell as BellIcon,
  BellOff as BellOffIcon,
  CheckCheck as CheckCheckIcon,
  BookOpen as BookOpenIcon,
  Mail as MailIcon,
  ShieldAlert as ShieldAlertIcon,
  RefreshCw as RefreshCwIcon
} from 'lucide-vue-next'

const config = useRuntimeConfig()
const authStore = useAuthStore()

const activeTab = ref<'notifications' | 'audit'>('notifications')
const filterType = ref('ALL')
const loading = ref(false)
const loadingAudit = ref(false)

const unreadCount = ref(0)
const notifications = ref<any[]>([])
const auditLogs = ref<any[]>([])

const fetchNotifications = async () => {
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/notifications`, {
      headers: { Authorization: `Bearer ${authStore.token}` },
      params: { filter: filterType.value }
    })
    unreadCount.value = res.unread_count
    notifications.value = res.notifications || []
  } catch (err: any) {
    console.error('Error fetching notifications:', err)
  } finally {
    loading.value = false
  }
}

const fetchAuditLogs = async () => {
  loadingAudit.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/audit-logs`, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    auditLogs.value = res || []
  } catch (err: any) {
    console.error('Error fetching audit logs:', err)
  } finally {
    loadingAudit.value = false
  }
}

const switchFilter = (type: string) => {
  activeTab.value = 'notifications'
  filterType.value = type
  fetchNotifications()
}

const switchToAudit = () => {
  activeTab.value = 'audit'
  fetchAuditLogs()
}

const markOneRead = async (item: any) => {
  if (item.is_read) return
  try {
    await $fetch(`${config.public.apiBase}/notifications/${item.id}/read`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    item.is_read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (err) {
    console.error('Failed to mark read', err)
  }
}

const markAllRead = async () => {
  try {
    await $fetch(`${config.public.apiBase}/notifications/all/read`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    notifications.value.forEach(n => (n.is_read = true))
    unreadCount.value = 0
  } catch (err) {
    console.error('Failed to mark all read', err)
  }
}

onMounted(() => {
  fetchNotifications()
})
</script>
