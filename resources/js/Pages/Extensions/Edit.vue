<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router,useForm } from '@inertiajs/vue3';
import { toRefs } from 'vue';

const props = defineProps({
    extension: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const { extension } = toRefs(props);

const form = useForm({
    name: extension.value?.name || '',
    extension: extension.value?.extension || '',
    display_name: extension.value?.display_name || '',
    context: extension.value?.context || 'from-internal',
    transport: extension.value?.transport || 'udp',
    caller_id_name: extension.value?.caller_id_name || '',
    caller_id_num: extension.value?.caller_id_num || '',
    timeout: extension.value?.timeout || 30,
    is_active: extension.value?.is_active ?? true,
    notes: extension.value?.notes || '',
});

const submit = () => {
    router.patch(route('extensions.update', extension.value.id), form);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Extension" />
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Extension
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                id="name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div v-if="props.errors.name" class="mt-1 text-sm text-red-600">
                                {{ props.errors.name }}
                            </div>
                        </div>

                        <div>
                            <label for="extension" class="block text-sm font-medium text-gray-700">Extension Number</label>
                            <input
                                v-model="form.extension"
                                type="text"
                                id="extension"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div v-if="props.errors.extension" class="mt-1 text-sm text-red-600">
                                {{ props.errors.extension }}
                            </div>
                        </div>

                        <div>
                            <label for="display_name" class="block text-sm font-medium text-gray-700">Display Name</label>
                            <input
                                v-model="form.display_name"
                                type="text"
                                id="display_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="context" class="block text-sm font-medium text-gray-700">Context</label>
                            <select
                                v-model="form.context"
                                id="context"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="from-internal">from-internal</option>
                                <option value="from-pstn">from-pstn</option>
                            </select>
                        </div>

                        <div>
                            <label for="caller_id_name" class="block text-sm font-medium text-gray-700">Caller ID Name</label>
                            <input
                                v-model="form.caller_id_name"
                                type="text"
                                id="caller_id_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="caller_id_num" class="block text-sm font-medium text-gray-700">Caller ID Number</label>
                            <input
                                v-model="form.caller_id_num"
                                type="text"
                                id="caller_id_num"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="timeout" class="block text-sm font-medium text-gray-700">Timeout (seconds)</label>
                            <input
                                v-model="form.timeout"
                                type="number"
                                id="timeout"
                                min="1"
                                max="300"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="is_active" class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="is_active"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <Link
                                :href="route('extensions.index')"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                :disabled="form.processing"
                                type="submit"
                                class="rounded-md bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600"
                            >
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>