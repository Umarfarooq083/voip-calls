<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router,useForm} from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
    success: {
        type: String,
        default: '',
    },
    voiceMessages: {
        type: Array,
        default: () => [],
    },
    extensions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    extension_id: '',
    voice_message_id: '',
    csv_file: null,
});

const selectedFile = ref('');

const submit = () => {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('extension_id', form.extension_id);
    if (form.voice_message_id) {
        formData.append('voice_message_id', form.voice_message_id);
    }
    if (form.csv_file) {
        formData.append('csv_file', form.csv_file);
    }

    router.post(route('campaigns.store'), formData, {
        forceProcessData: true,
        preserveContentType: true,
        onSuccess: () => {
            router.visit(route('campaigns.index'), {
                replace: true,
            });
        },
    });
};

const handleFileChange = (event) => {
    if (event.target.files && event.target.files[0]) {
        form.csv_file = event.target.files[0];
        selectedFile.value = event.target.files[0].name;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Campaign" />
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Create Campaign
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Campaign Name</label>
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
                            <label for="extension_id" class="block text-sm font-medium text-gray-700">Extension</label>
                            <select
                                v-model="form.extension_id"
                                id="extension_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select Extension</option>
                                <option v-for="extension in props.extensions" :key="extension.id" :value="extension.id">
                                    {{ extension.name }} ({{ extension.extension }})
                                </option>
                            </select>
                            <div v-if="props.errors.extension_id" class="mt-1 text-sm text-red-600">
                                {{ props.errors.extension_id }}
                            </div>
                        </div>

                        <div>
                            <label for="voice_message_id" class="block text-sm font-medium text-gray-700">Voice Message</label>
                            <select
                                v-model="form.voice_message_id"
                                id="voice_message_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">No Voice Message</option>
                                <option v-for="voiceMessage in props.voiceMessages" :key="voiceMessage.id" :value="voiceMessage.id">
                                    {{ voiceMessage.name }}
                                </option>
                            </select>
                            <div v-if="props.errors.voice_message_id" class="mt-1 text-sm text-red-600">
                                {{ props.errors.voice_message_id }}
                            </div>
                        </div>

                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700">CSV File (customer_name, phone_number)</label>
                            <input
                                @change="handleFileChange"
                                type="file"
                                id="csv_file"
                                accept=".csv,.txt"
                                class="mt-1 block w-full"
                            />
                            <div v-if="selectedFile" class="mt-1 text-sm text-gray-600">
                                Selected: {{ selectedFile }}
                            </div>
                            <div v-if="props.errors.csv_file" class="mt-1 text-sm text-red-600">
                                {{ props.errors.csv_file }}
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                CSV format: customer_name, phone_number (one contact per line)
                            </p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <Link
                                :href="route('campaigns.index')"
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