<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, toRefs } from 'vue';

const props = defineProps({
    extensions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
    links: {
        type: Object,
        default: () => ({}),
    },
    success: {
        type: String,
        default: '',
    },
});

const { filters } = toRefs(props);
const search = ref(filters.value?.search || '');

let timer = null;
const handleSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            route('extensions.index'),
            { search: search.value },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 900);
};

const deleteExtension = (id) => {
    if (confirm('Are you sure you want to delete this extension?')) {
        router.delete(route('extensions.destroy', { extension: id }));
    }
};
</script>

<template>
    <AuthenticatedLayout>

        <Head title="Extensions" />
        <template #header>
            <h3 class="text-xl font-semibold leading-tight text-gray-800">
                Extensions
            </h3>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <input v-model="search" type="text" placeholder="Search..."
                            class="border border-gray-300 rounded-md px-3 py-2 w-64" @input="handleSearch" />
                    </div>
                    <Link :href="route('extensions.create')"
                        class="rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-600">
                        Create Extension
                    </Link>
                </div>

                <div v-if="success" class="mb-4 rounded-md bg-green-100 p-4 text-sm text-green-700">
                    {{ success }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Extension
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Context
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="extension in extensions" :key="extension.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ extension.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ extension.extension }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ extension.context }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span
                                        :class="{ 'text-green-600': extension.is_active, 'text-gray-600': !extension.is_active }">
                                        {{ extension.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                    <Link :href="route('extensions.edit', { extension: extension.id })"
                                        class="hover:underline mr-4">
                                        Edit
                                    </Link>
                                    <button @click="deleteExtension(extension.id)" class="hover:underline text-red-600">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-center">
                    <div class="flex space-x-2">
                        <button v-if="meta.current_page > 1" @click="router.get(links.prev)"
                            class="px-3 py-1 rounded-md border border-gray-300 text-sm">
                            Previous
                        </button>
                        <button v-if="meta.current_page < meta.last_page" @click="router.get(links.next)"
                            class="px-3 py-1 rounded-md border border-gray-300 text-sm">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>