<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    extension: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Extension Details" />

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ extension.name }}
                            </h3>
                            <span
                                :class="{
                                    'text-green-600': extension.registered,
                                    'text-gray-600': !extension.registered,
                                }"
                                class="text-sm font-medium"
                            >
                                {{ extension.registered ? 'Registered' : 'Not Registered' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Extension Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.extension }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Display Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.display_name || '-' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Context</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.context }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Transport</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.transport }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Caller ID Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.caller_id_name || '-' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Caller ID Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.caller_id_num || '-' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Timeout</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.timeout }} seconds</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <p class="mt-1 text-sm text-gray-900">{{ extension.is_active ? 'Active' : 'Inactive' }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Last Registered</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ extension.last_registered_at ? new Date(extension.last_registered_at).toLocaleString() : 'Never' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <Link
                                :href="route('extensions.index')"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Back to List
                            </Link>
                            <Link
                                :href="route('extensions.edit', { extension: extension.id || extension.extension })"
                                class="rounded-md bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>