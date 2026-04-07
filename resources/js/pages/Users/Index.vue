<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { ref, watch } from 'vue';
import { DownloadCloud, UploadCloud, Plus, Search, Edit, Trash2, CheckCircle2, X } from 'lucide-vue-next';

interface Unit { id: number; name: string; }
interface Department { id: number; unit_id: number; name: string; }

const photoPreview = ref<string | null>(null);
const profilePhotoInput = ref<HTMLInputElement | null>(null);

const triggerProfilePhotoUpload = () => { profilePhotoInput.value?.click(); };

const handleProfilePhotoUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if(!file) return;
    userForm.photo = file as any;
    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.value = e.target?.result as string; };
    reader.readAsDataURL(file);
};

const props = defineProps<{
  users: {
      data: any[];
      total: number;
      prev_page_url: string | null;
      next_page_url: string | null;
  };
  roles: any[];
  stands: any[];
  units: Unit[];
  departments: Department[];
  filters: any;
}>();

// Filtros Reactivos
const formFilters = useForm({
  search: props.filters?.search || '',
  role_id: props.filters?.role_id || '',
  status: props.filters?.status || 'active',
});

// Auto-submit para los filtros (con pequeño retraso)
let searchTimeout: ReturnType<typeof setTimeout>;
watch(() => [formFilters.search, formFilters.role_id, formFilters.status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        formFilters.get('/users', { preserveState: true, preserveScroll: true });
    }, 300);
}, { deep: true });

// Controladores de Modales
const showUserModal = ref(false);
const showResetModal = ref(false);
const isEditing = ref(false);

const userForm = useForm({
  id: null,
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  extension: '',
  tshirt_size: '',
  role_id: '',
  stand_id: '',
  unit_id: '',
  department_id: '',
  custom_unit: '',
  custom_department: '',
  is_active: true,
  photo: null,
  delete_photo: false,
  dni: '',
});

const openCreateModal = () => {
  isEditing.value = false;
  userForm.reset();
  userForm.delete_photo = false;
  photoPreview.value = null;
  showUserModal.value = true;
};

const removePhoto = () => {
    userForm.photo = null;
    userForm.delete_photo = true;
    photoPreview.value = null;
};

const openEditModal = (user: any) => {
  isEditing.value = true;
  userForm.reset();
  userForm.id = user.id;
  
  userForm.first_name = user.first_name || '';
  userForm.last_name = user.last_name || '';
  
  userForm.email = user.email || '';
  userForm.phone = user.phone || '';
  userForm.extension = user.extension || '';
  userForm.tshirt_size = user.tshirt_size || '';
  userForm.role_id = user.role_id || '';
  userForm.stand_id = user.stand_id || '';
  userForm.unit_id = user.unit_id || (user.custom_unit ? 'other' : '');
  userForm.department_id = user.department_id || (user.custom_department ? 'other' : '');
  userForm.custom_unit = user.custom_unit || '';
  userForm.custom_department = user.custom_department || '';
  userForm.is_active = !!user.is_active; // cast a booleano safer
  userForm.delete_photo = false;
  userForm.dni = user.dni || '';
  photoPreview.value = user.profile_photo_path ? '/storage/' + user.profile_photo_path : null;
  
  showUserModal.value = true;
};

const saveUser = () => {
  const data: any = { ...userForm.data() };
  if (data.unit_id === 'other') data.unit_id = null;
  if (data.department_id === 'other') data.department_id = null;

  if (isEditing.value) {
     data._method = 'put';
     router.post('/users/' + userForm.id, data, {
        onSuccess: () => { showUserModal.value = false; userForm.reset(); photoPreview.value = null; }
     });
  } else {
     router.post('/users', data, {
        onSuccess: () => { showUserModal.value = false; userForm.reset(); photoPreview.value = null; }
     });
  }
};

const deleteUser = (id: number) => {
  if(confirm('¿Estás seguro de desactivar este usuario?')) {
      router.delete('/users/' + id, {
          preserveScroll: true
      });
  }
};

const sendResetPassword = () => {
    router.post('/users/' + userForm.id + '/reset-password', {}, {
        onSuccess: () => {
            showResetModal.value = true;
        }
    });
};

// Eliminar Permanente
const confirmForceDelete = (user: any) => {
    if (confirm(`¿Estás seguro de eliminar a ${user.name} PERMANENTEMENTE? Esta acción no se puede deshacer y borrará todos sus datos del sistema.`)) {
        router.delete(`/users/${user.id}/force-delete`, {
            onSuccess: () => alert('Usuario eliminado de forma definitiva.')
        });
    }
};

// Carga CSV Múltiple
const fileInput = ref<HTMLInputElement | null>(null);
const triggerFileUpload = () => { fileInput.value?.click(); };
const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if(!file) return;
    const form = useForm({ csv_file: file });
    form.post('/users/import/csv', {
       onSuccess: () => { 
           alert('¡Carga de plantilla procesada exitosamente!'); 
           target.value = ''; 
       }
    });
};
</script>

<template>
<AppLayout :breadcrumbs="[{title: 'Usuarios', href: '/users'}]">
    <Head title="Administrador de usuarios" />
    
    <div class="px-8 py-8 w-full max-w-7xl mx-auto space-y-6 min-h-screen">
        
        <!-- Header -->
        <h1 class="text-3xl font-normal text-gray-900 dark:text-white mb-8 tracking-tight">Administrador de usuarios</h1>

        <!-- Toolbar (Search, Filter and Action Buttons) -->
        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4 mt-6">
            
            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                <!-- Buscador -->
                <div class="flex flex-col">
                    <label class="text-[11px] text-gray-600 dark:text-gray-400 font-medium uppercase tracking-wide">Nombre / Correo / DNI</label>
                    <div class="relative w-full sm:w-64">
                        <Search class="absolute left-3 top-[11px] h-4 w-4 text-gray-500" />
                        <input v-model="formFilters.search" type="text" class="w-full pl-9 pr-4 py-2 border border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-1 focus:ring-black dark:focus:ring-white focus:border-black dark:focus:border-white sm:text-sm dark:bg-zinc-800 dark:text-white" placeholder="Buscar">
                    </div>
                </div>

                <!-- Tipo de usuario Rol -->
                <div class="flex flex-col">
                    <label class="text-[11px] text-gray-600 dark:text-gray-400 font-medium uppercase tracking-wide">Perfil de usuario</label>
                    <select v-model="formFilters.role_id" class="w-full sm:w-48 pl-3 pr-8 py-2 border border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-1 focus:ring-black dark:focus:ring-white focus:border-black dark:focus:border-white sm:text-sm dark:bg-zinc-800 dark:text-white">
                        <option value="">Todo</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Botonera Acciones (Multiple CSV, Descarga CSV, + Añadir) -->
            <div class="flex items-center gap-3 self-start xl:self-auto">
                <input type="file" ref="fileInput" class="hidden" accept=".csv" @change="handleFileUpload">
                
                <button @click="triggerFileUpload" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <UploadCloud class="w-4 h-4" /> Multiple
                </button>
                
                <a href="/users/export/csv" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <DownloadCloud class="w-4 h-4" /> CSV
                </a>
                
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    <Plus class="w-4 h-4" /> Añadir
                </button>
            </div>
        </div>

        <!-- Tabla y Controles en Panel Blanco Estilizado -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-6">
            
            <!-- Tabs (Activos e Inactivos) -->
            <div class="border-b border-gray-200">
                <nav class="flex px-4" aria-label="Tabs">
                    <button @click="formFilters.status = 'active'" :class="[formFilters.status === 'active' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-6 border-b-2 font-medium text-sm transition-colors duration-200']">
                        Activos <span class="ml-1 text-xs">({{ formFilters.status === 'active' ? (users?.total || 0) : '--' }})</span>
                    </button>
                    <button @click="formFilters.status = 'inactive'" :class="[formFilters.status === 'inactive' ? 'border-black text-black' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-3 px-6 border-b-2 font-medium text-sm transition-colors duration-200']">
                        Inactivos <span class="ml-1 text-xs">({{ formFilters.status === 'inactive' ? (users?.total || 0) : '--' }})</span>
                    </button>
                </nav>
            </div>

            <!-- Tabla de Datos -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                    <thead class="bg-white dark:bg-zinc-900 border-b dark:border-zinc-800">
                        <tr>
                            
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">Perfil</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">Unidad / Depto</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">Stand</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">Playera</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">DNI</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-gray-200 tracking-wider">Status</th>
                            <th scope="col" class="relative px-6 py-4"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-800">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors duration-150">
                            
                            

                            <!-- Avatar + Nombre -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-[38px] w-[38px] flex-shrink-0 rounded-full border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex items-center justify-center overflow-hidden">
                                        <img v-if="user.profile_photo_path" :src="'/storage/' + user.profile_photo_path" class="h-full w-full object-cover">
                                        <svg v-else class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ user.first_name }} {{ user.last_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <!-- Email -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</td>
                            <!-- Perfil  -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ user.role?.name || '-' }}</td>
                            <!-- Unidad / Depto -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex flex-col">
                                    <span>{{ user.unit?.name || user.custom_unit || '-' }}</span>
                                    <span class="text-[10px] text-gray-400 dark:text-zinc-600">{{ user.department?.name || user.custom_department || '-' }}</span>
                                </div>
                            </td>
                            <!-- Stand -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ user.stand?.name || '-' }}</td>
                            <!-- Playera -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ user.tshirt_size || '-' }}</td>
                            <!-- DNI -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">{{ user.dni }}</td>
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                {{ user.is_active ? 'Activo' : 'Inactivo' }}
                            </td>
                            <!-- Acciones Edit / Delete -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEditModal(user)" class="text-gray-600 hover:text-black dark:text-gray-400 dark:hover:text-white border border-gray-300 dark:border-zinc-700 rounded p-1.5 shadow-sm transition-colors bg-white dark:bg-zinc-800">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button v-if="user.is_active" @click="deleteUser(user.id)" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-300 dark:border-zinc-700 rounded p-1.5 shadow-sm transition-colors bg-white dark:bg-zinc-800">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                    <button v-else @click="confirmForceDelete(user)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 border border-red-200 dark:border-red-900 rounded p-1.5 shadow-sm transition-colors bg-red-50 dark:bg-red-900/20">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>

                        </tr>
                        
                        <tr v-if="!users || !users.data || users.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No se encontraron registros.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación Simplificada -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex justify-end gap-4 text-sm font-medium" v-if="users.total > 0">
                 <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="text-gray-900 dark:text-white hover:underline">Anterior</Link>
                 <span v-else class="text-gray-400 dark:text-zinc-600">Anterior</span>

                 <Link v-if="users.next_page_url" :href="users.next_page_url" class="text-gray-900 dark:text-white hover:underline">Siguiente</Link>
                 <span v-else class="text-gray-400 dark:text-zinc-600">Siguiente</span>
            </div>
        </div>

    </div>

    <!-- MAIN ADD / EDIT USER MODAL -->
    <div v-if="showUserModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay oscuro (Ajustado a 50% opacidad para Tailwind v4) -->
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showUserModal = false"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Caja Principal Modal Rounded-3xl (Acorde al wireframe) -->
            <div class="inline-block align-bottom bg-white dark:bg-zinc-900 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full p-8 relative border dark:border-zinc-800">
                
                <!-- Botón de Cerrar (X) Top Right -->
                <button @click="showUserModal = false" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                    <X class="w-6 h-6" />
                </button>
                
                <!-- Avatar Circular y Botón de Restablecer -->
                <div class="flex justify-between items-center mb-10 mt-2">
                    <div class="flex items-center gap-6">   
                        <div class="relative cursor-pointer group" @click="triggerProfilePhotoUpload">
                            <input type="file" ref="profilePhotoInput" class="hidden" accept="image/*" @change="handleProfilePhotoUpload">
                            <!-- Círculo de Avatar -->
                            <div class="h-20 w-20 rounded-full border border-gray-900 dark:border-zinc-700 flex items-center justify-center bg-white dark:bg-zinc-800 overflow-hidden">
                            <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover">
                            <svg v-else class="h-12 w-12 text-gray-800 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            </div>
                            <!-- Círculo flotante de cámara (icono decorativo como en el wireframe) -->
                            <div class="absolute bottom-0 right-0 h-[22px] w-[22px] bg-white dark:bg-zinc-800 border border-gray-900 dark:border-zinc-700 rounded-full flex items-center justify-center group-hover:bg-gray-100 dark:group-hover:bg-zinc-700 transition-colors">
                            <svg class="w-3 h-3 text-gray-800 dark:text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <!-- Botón eliminar foto (Solo si hay una previa o cargada) -->
                            <button v-if="photoPreview" @click.stop.prevent="removePhoto" class="absolute bottom-0 right-0 h-[22px] w-[22px] bg-white dark:bg-zinc-800 border border-gray-900 dark:border-zinc-700 rounded-full text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700 flex items-center justify-center transition-colors shadow-sm" title="Quitar foto">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <!-- DNI Read Only -->
                        <div v-if="isEditing" class="flex flex-col">
                            <label class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-1">DNI Identificador</label>
                            <span class="text-lg font-mono text-gray-900 dark:text-gray-200 bg-gray-50 dark:bg-zinc-800 px-3 py-1 rounded border border-gray-200 dark:border-zinc-700">{{ userForm.dni }}</span>
                        </div>
                </div>

                    <!-- Botón exclusivo de edición -->
                    <button v-if="isEditing" @click.prevent="sendResetPassword" class="px-5 py-2.5 border border-gray-900 dark:border-zinc-700 rounded-md text-sm font-medium text-gray-900 dark:text-gray-200 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                        Restablecer contraseña
                    </button>
                    <!-- Close (Cancel) para cuando no es edit (agregado por practicidad) -->
                    <button v-if="!isEditing" @click="showUserModal = false" class="text-sm text-gray-500 hover:text-gray-900">
                       Cerrar
                    </button>
                </div>

                   <!-- Formulario -->
                <form @submit.prevent="saveUser">
                    <div class="grid grid-cols-1 gap-y-7 gap-x-6 sm:grid-cols-2">
                        
                        <!-- SECCIÓN: Datos personales -->
                        <div class="col-span-full border-b border-gray-100 dark:border-zinc-800 pb-2 mb-2">
                            <h4 class="text-[11px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Datos personales</h4>
                        </div>

                        <!-- Block: Nombres -->
                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Nombre/s *</label>
                            <input v-model="userForm.first_name" type="text" required class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="Jhon">
                        </div>

                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Apellido/s *</label>
                            <input v-model="userForm.last_name" type="text" required class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="Due">
                        </div>

                        <!-- Block: Correo -->
                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Correo</label>
                            <input v-model="userForm.email" type="email" required class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="jhon@mail.com">
                            <p class="text-red-500 text-xs mt-1" v-if="userForm.errors.email">{{ userForm.errors.email }}</p>
                        </div>

                        <!-- Block: Teléfonos -->
                        <div class="flex gap-4">
                            <div class="flex-grow">
                                <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Teléfono</label>
                                <input v-model="userForm.phone" type="text" class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="+52 5512345678">
                            </div>
                            <div class="w-20">
                                <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Ext.</label>
                                <input v-model="userForm.extension" type="text" class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="6062">
                            </div>
                        </div>

                        <!-- SECCIÓN: Área -->
                        <div class="col-span-full border-b border-gray-100 dark:border-zinc-800 pb-2 mt-4 mb-2">
                            <h4 class="text-[11px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Área</h4>
                        </div>

                        <!-- Block: Unidad y Departamento -->
                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Unidad de adscripción</label>
                            <select v-model="userForm.unit_id" required class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white">
                                <option value="">Selección</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                <option value="other">Otra</option>
                            </select>
                            <input v-if="userForm.unit_id === 'other'" v-model="userForm.custom_unit" type="text" required class="mt-2 w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="Especifique unidad">
                        </div>

                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Departamento</label>
                            <select v-model="userForm.department_id" required class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white">
                                <option value="">Selección</option>
                                <template v-if="userForm.unit_id && userForm.unit_id !== 'other'">
                                    <option v-for="dept in (departments || []).filter(d => (d.unit_id?.toString()) == userForm.unit_id.toString())" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                </template>
                                <option value="other">Otra</option>
                            </select>
                            <input v-if="userForm.department_id === 'other'" v-model="userForm.custom_department" type="text" required class="mt-2 w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white" placeholder="Especifique departamento">
                        </div>

                        <!-- SECCIÓN: Stand -->
                        <div class="col-span-full border-b border-gray-100 dark:border-zinc-800 pb-2 mt-4 mb-2">
                            <h4 class="text-[11px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Stand</h4>
                        </div>

                        <!-- Block: Talla y Perfil -->
                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Tamaño playera</label>
                            <select v-model="userForm.tshirt_size" class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white">
                                <option value="">Selección</option>
                                <option value="S">CH (S)</option>
                                <option value="M">M</option>
                                <option value="L">G (L)</option>
                                <option value="XL">XL</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Perfil de usuario</label>
                            <select v-model="userForm.role_id" required class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white">
                                <option value="">Selección</option>
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                        </div>

                        <!-- Block: Stand y Status -->
                        <div>
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Stand</label>
                            <select v-model="userForm.stand_id" class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white">
                                <option value="">Selección</option>
                                <option v-for="stand in (stands || [])" :key="stand.id" :value="stand.id">{{ stand.name }}</option>
                            </select>
                        </div>

                        <div v-if="isEditing">
                            <label class="block text-[13px] font-medium text-gray-900 dark:text-gray-200 mb-1">Status</label>
                            <select v-model="userForm.is_active" class="w-full rounded border-gray-300 dark:border-zinc-700 shadow-sm focus:border-black dark:focus:border-white focus:ring-black dark:focus:ring-white sm:text-sm p-2 border dark:bg-zinc-800 dark:text-white">
                                <option :value="true">Activo</option>
                                <option :value="false">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-12 flex justify-start">
                         <!-- Botón Submit -->
                         <button type="submit" :disabled="userForm.processing" class="inline-flex justify-center items-center rounded border border-gray-900 dark:border-zinc-700 shadow-sm px-5 py-2.5 bg-white dark:bg-zinc-800 text-sm font-medium text-gray-900 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-zinc-700 focus:outline-none transition group">
                             <!-- Icono Save para Guardar, Icono Plus para Añadir -->
                             <span v-if="!isEditing" class="flex gap-2 items-center"><Plus class="w-4 h-4"/> Añadir</span>
                             <span v-else class="flex gap-2 items-center">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                                 Guardar cambios
                             </span>
                         </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Modal Success de Contraseña -->
    <div v-if="showResetModal" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showResetModal = false"></div>
            
            <div class="inline-block bg-white dark:bg-zinc-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md sm:w-full p-10 text-center relative z-10 border dark:border-zinc-800">
                <!-- Botón de Cerrar (X) para el modal de éxito -->
                <button @click="showResetModal = false" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                    <X class="w-5 h-5" />
                </button>
                
                <div class="mx-auto flex items-center justify-center h-[70px] w-[70px] rounded-full border border-gray-400 dark:border-zinc-700 bg-white dark:bg-zinc-800 mb-6 shadow-sm">
                    <CheckCircle2 class="h-[34px] w-[34px] text-gray-800 dark:text-gray-200" />
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-[17px] font-medium text-gray-900 dark:text-white" id="modal-title">
                        Se envió un correo al usuario para<br>restablecer su contraseña
                    </h3>
                </div>
                <div class="mt-8 flex justify-center">
                    <button type="button" class="inline-flex justify-center rounded-md border border-gray-300 dark:border-zinc-700 shadow-sm px-6 py-2 bg-white dark:bg-zinc-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700 transition" @click="showResetModal = false">
                        Cerrar mensaje
                    </button>
                </div>
            </div>
        </div>
    </div>

</AppLayout>
</template>
