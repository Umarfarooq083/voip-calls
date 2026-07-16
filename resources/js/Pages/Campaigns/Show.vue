<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    campaign: {
        type: Object,
        default: () => ({}),
    },
    contacts: {
        type: Array,
        default: () => [],
    },
    success: {
        type: String,
        default: '',
    },
});

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        called: 'bg-blue-100 text-blue-800',
        successful: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        skipped: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const deleteContact = (id) => {
    if (confirm('Are you sure you want to delete this contact?')) {
        router.delete(route('campaign_contacts.destroy', { contact: id }));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Campaign Details" />
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Campaign Details
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="success" class="mb-4 rounded-md bg-green-100 p-4 text-sm text-green-700">
                    {{ success }}
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ campaign.name }}</h3>
                                <p class="text-sm text-gray-600">
                                    Status:
                                    <span :class="getStatusBadgeClass(campaign.status)"
                                        class="px-2 py-1 rounded-full text-xs font-medium ml-1">
                                        {{ campaign.status.replace('_', ' ') }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <Link :href="route('campaigns.edit', { campaign: campaign.id })"
                                    class="rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-600">
                                    Edit
                                </Link>
                                <button @click="router.delete(route('campaigns.destroy', { campaign: campaign.id }))"
                                    class="rounded-md bg-red-500 px-4 py-2 text-sm font-semibold text-white hover:bg-red-600">
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Extension</p>
                                <p class="text-sm text-gray-900">{{ campaign.extension?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Voice Message</p>
                                <p class="text-sm text-gray-900">{{ campaign.voice_message?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Contacts</p>
                                <p class="text-sm text-gray-900">{{ campaign.total_contacts }}</p>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-3 gap-4">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <p class="text-2xl font-bold text-blue-600">{{ campaign.called_contacts }}</p>
                                <p class="text-xs text-gray-500">Called</p>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <p class="text-2xl font-bold text-green-600">{{ campaign.successful_calls }}</p>
                                <p class="text-xs text-gray-500">Successful</p>
                            </div>
                            <div class="text-center p-4 bg-red-50 rounded-lg">
                                <p class="text-2xl font-bold text-red-600">{{ campaign.failed_calls }}</p>
                                <p class="text-xs text-gray-500">Failed</p>
                            </div>
                        </div>

                        <div v-if="campaign.notes" class="mt-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Notes</p>
                            <p class="text-sm text-gray-700">{{ campaign.notes }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contacts ({{ contacts.length }})</h3>
                        
                        <div v-if="contacts.length === 0" class="text-gray-500 py-4">
                            No contacts imported yet.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Customer Name
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Phone Number
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
                                    <tr v-for="contact in contacts" :key="contact.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ contact.customer_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ contact.phone_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span :class="getStatusBadgeClass(contact.status)"
                                                class="px-2 py-1 rounded-full text-xs font-medium">
                                                {{ contact.status.replace('_', ' ') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                            <button @click="deleteContact(contact.id)" class="hover:underline text-red-600">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <Link :href="route('campaigns.index')"
                        class="rounded-md bg-gray-500 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600">
                        Back to Campaigns
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>