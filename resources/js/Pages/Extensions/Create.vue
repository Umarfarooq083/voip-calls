<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm,Head, router } from '@inertiajs/vue3';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
    success: {
        type: String,
        default: '',
    },
});

const form = useForm({
    name: '',
    extension: '',
    secret: '',
    display_name: '',
    context: 'from-internal',
    transport: 'udp',
    caller_id_name: '',
    caller_id_num: '',
    mailbox: '',
    vm_context: 'default',
    timeout: 30,
    is_active: true,
    notes: '',
});

const submit = () => {
    router.post(route('extensions.store'), form, {
        onSuccess: () => {
            router.visit(route('extensions.index'), {
                replace: true,
            });
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Extension" />
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Create Extension
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
                            <label for="secret" class="block text-sm font-medium text-gray-700">Secret (Password)</label>
                            <input
                                v-model="form.secret"
                                type="password"
                                id="secret"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div v-if="props.errors.secret" class="mt-1 text-sm text-red-600">
                                {{ props.errors.secret }}
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
                                <option value="from-internal">from-internal</option>
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
                                Create
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>