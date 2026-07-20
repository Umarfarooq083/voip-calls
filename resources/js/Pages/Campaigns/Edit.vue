<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router,useForm } from '@inertiajs/vue3';
import { ref,watch } from 'vue';

const props = defineProps({
    campaign: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    success: {
        type: String,
        default: '',
    },
    ivrs: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.campaign?.name || '',
    no_of_calls: props.campaign?.no_of_calls,
    ivr_id: props.campaign?.ivr_id || '',
    ivr_name: props.campaign?.ivr_name || '',   
    csv_file: null,
});

const selectedFile = ref('');
const handleFileChange = (event) => {
    if (event.target.files && event.target.files[0]) {
        form.csv_file = event.target.files[0];
        selectedFile.value = event.target.files[0].name;
    }
};

watch(() => form.ivr_id, (newValue) => {
    const ivr = props.campaign.ivrs.find(item => item.id == newValue);
    form.ivr_name = ivr ? ivr.name : '';
});


const submit = () => {
    form.post(route('campaigns.update', { campaign: props.campaign?.id }), {
        method: 'PATCH',
        onSuccess: () => {
            router.visit(route('campaigns.index'), {
                replace: true,
            });
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Campaign" />
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Campaign
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
                            <label for="no_of_calls" class="block text-sm font-medium text-gray-700">Number of Calls</label>
                            <input
                                v-model="form.no_of_calls"
                                type="text"
                                id="no_of_calls"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div v-if="props.errors.no_of_calls" class="mt-1 text-sm text-red-600">
                                {{ props.errors.no_of_calls }}
                            </div>
                        </div>
                        
                        <div>
                            <label for="ivr_id" class="block text-sm font-medium text-gray-700">IVR</label>
                            <select
                                v-model="form.ivr_id"
                                id="ivr_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select an IVR</option>
                                <option v-for="ivr in props?.campaign?.ivrs" :key="ivr.id" :value="ivr.id">
                                    {{ ivr?.name}} 
                                </option>
                            </select>
                            <div v-if="props.errors.ivr_id" class="mt-1 text-sm text-red-600">
                                {{ props.errors.ivr_id }}
                            </div>

                        <input type="hidden" v-model="form.ivr_name">


                        </div>
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700">CSV File (phone_number)</label>
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
                                CSV format: phone_number (one contact per line). customer_name column is optional.
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
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>