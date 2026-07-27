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
    statusCounts: {
        type: Object,
        default: () => ({}),
    },
});

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        called: 'bg-blue-100 text-blue-800',
        successful: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        skipped: 'bg-gray-100 text-gray-500',
        busy: 'bg-purple-100 text-purple-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const startCalling = () => {
    router.get(route('campaigns.startcalling', { campaign: campaign.id }));
};

const retryFailedCalls = () => {
    if (confirm('Are you sure you want to retry all failed, busy, and unanswered calls?')) {
        router.post(route('campaigns.retryfailed', { campaign: campaign.id }));
    }
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
                                <!-- <button @click="startCalling"
                                    class="rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-600">
                                    Start Calling
                                </button> -->
                                <!-- <button @click="retryFailedCalls"
                                    class="rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600">
                                    Retry Failed Calls
                                </button> -->
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Contacts</p>
                                <p class="text-sm text-gray-900">{{ campaign.total_contacts }}</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Status Summary</h4>
                            <div class="grid grid-cols-6 gap-4">
                                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                    <p class="text-2xl font-bold text-yellow-600">{{ statusCounts.pending || 0 }}</p>
                                    <p class="text-xs text-gray-500">Pending</p>
                                </div>
                                <!-- <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <p class="text-2xl font-bold text-blue-600">{{ statusCounts.calling || 0 }}</p>
                                    <p class="text-xs text-gray-500">Calling</p>
                                </div> -->
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <p class="text-2xl font-bold text-purple-600">{{ statusCounts.ringing || 0 }}</p>
                                    <p class="text-xs text-gray-500">Ringing</p>
                                </div>
                                <div class="text-center p-4 bg-purple-50 rounded-lg">
                                    <p class="text-2xl font-bold text-purple-600">{{ statusCounts.ringing || 0 }}</p>
                                    <p class="text-xs text-gray-500">Move to Agent</p>
                                </div>
                                <div class="text-center p-4 bg-red-50 rounded-lg">
                                    <p class="text-2xl font-bold text-red-600">{{ statusCounts.failed || 0 }}</p>
                                    <p class="text-xs text-gray-500">Failed</p>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <p class="text-2xl font-bold text-green-600">{{ statusCounts.success || 0 }}</p>
                                    <p class="text-xs text-gray-500">Success</p>
                                </div>
                               
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