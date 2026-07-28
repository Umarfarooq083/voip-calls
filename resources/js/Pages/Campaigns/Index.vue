<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, toRefs } from 'vue';

const props = defineProps({
    campaigns: {
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
    error: {
        type: String,
        default: '',
    },
    inProgressCampaigns: {
        type: Array,
        default: () => [],
    },
});

const { filters } = toRefs(props);
const search = ref(filters.value?.search || '');
let timer = null;

const handleSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            route('campaigns.index'),
            { search: search.value },
            {
                preserveState: true,
                replace: true,
            }
        );
    }, 900);
};


const formatDate = (date) => {
    if (!date) return 'N/A';

    return new Date(date).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};


const deleteCampaign = (id) => {
    if (confirm('Are you sure you want to delete this campaign?')) {
        router.delete(route('campaigns.destroy', { campaign: id }));
    }
};

const startCalling = (campaignId) => {
    router.get(route('campaigns.startcalling', { campaign: campaignId }));
};

const retryFailedCalls = (campaignId) => {
    if (confirm('Are you sure you want to retry all failed, busy, and unanswered calls?')) {
        router.post(route('campaigns.retryfailed', { campaign: campaignId }));
    }
};

const stopCampaign = (campaignId) => {
    if (confirm('Are you sure you want to stop this campaign? Pending contacts will not be called.')) {
        router.post(route('campaigns.stop', { campaign: campaignId }));
    }
};

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        paused: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Campaigns" />
        <template #header>
            <h3 class="text-xl font-semibold leading-tight text-gray-800">
                Campaigns
            </h3>
        </template>
        <div class="py-12">
            <div class="sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <input v-model="search" type="text" placeholder="Search..."
                            class="border border-gray-300 rounded-md px-3 py-2 w-64" @input="handleSearch" />
                        <select v-model="filters.status" @change="router.get(route('campaigns.index'), { status: filters.status })"
                            class="border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="paused">Paused</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <Link :href="route('campaigns.create')"
                        class="rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-600">
                        Create Campaign
                    </Link>
                </div>

                <div v-if="success" class="mb-4 rounded-md bg-green-100 p-4 text-sm text-green-700">
                    {{ success }}
                </div>

                <div v-if="error" class="mb-4 rounded-md bg-red-100 p-4 text-sm text-red-700">
                    {{ error }}
                </div>

                <div v-if="inProgressCampaigns.length > 0" class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">In Progress Campaigns</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="campaign in inProgressCampaigns" :key="campaign.id" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="text-sm font-semibold text-gray-900">{{ campaign.name }}</h4>
                                <span :class="getStatusBadgeClass(campaign.status)"
                                    class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ campaign.status.replace('_', ' ') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-lg font-bold text-yellow-600">{{ campaign.status_counts.pending }}</p>
                                    <p class="text-xs text-gray-500">Pending</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-red-600">{{ campaign.status_counts.failed }}</p>
                                    <p class="text-xs text-gray-500">Failed</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-green-600">{{ campaign.status_counts.attended }}</p>
                                    <p class="text-xs text-gray-500">Attended</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-green-600">{{ campaign.status_counts.calling_ringing }}</p>
                                    <p class="text-xs text-gray-500">Ringing</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-green-600">{{ campaign.status_counts['1_pressed'] }}</p>
                                    <p class="text-xs text-gray-500">Move to Agent</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-green-600">{{ campaign.status_counts.successful }}</p>
                                    <p class="text-xs text-gray-500">Successful</p>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <Link :href="route('campaigns.show', { campaign: campaign.id })"
                                    class="text-sm text-indigo-600 hover:underline">
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    NO OF CALLS
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sip Trunk
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    IVR
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contacts
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Start At
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Completed
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="campaign in campaigns" :key="campaign.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ campaign.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ campaign.no_of_calls }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ campaign?.trunk_channalId }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ campaign?.ivr_name || 'N/A' }}
                                </td> 
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ campaign.total_contacts }} contacts
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span :class="getStatusBadgeClass(campaign.status)"
                                        class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ campaign.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                  <!-- {{ new Date(campaign?.started_at).toLocaleString() }}  -->
                                    <!-- {{ new Date(campaign?.started_at).toLocaleDateString() }} -->
                                    {{ formatDate(campaign?.started_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(campaign?.completed_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <!-- Start Calling Button -->
                                    <button style="padding: 8px;"
                                        v-if="campaign.status === 'pending'" 
                                        @click="startCalling(campaign.id)" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors mr-2"
                                        title="Start calling campaign"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <!-- Start Calling -->
                                    </button>

                                    <!-- Retry Failed Calls Button -->
                                    <button style="padding: 8px;"
                                        @click="retryFailedCalls(campaign.id)" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors mr-2"
                                        title="Retry or resume calls"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <!-- Retry Failed -->
                                    </button>

                                    <!-- Stop Campaign Button -->
                                    <button v-if="campaign.status === 'in_progress'" style="padding: 8px;"
                                        @click="stopCampaign(campaign.id)" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors mr-2"
                                        title="Stop campaign"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <!-- Stop -->
                                    </button>

                                    <!-- Edit Button -->
                                    <Link style="padding: 8px;"
                                        :href="route('campaigns.edit', { campaign: campaign.id })"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors mr-2"
                                        title="Edit campaign"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <!-- Edit -->
                                    </Link>

                                    <!-- View Button -->
                                    <Link style="padding: 8px;"
                                        :href="route('campaigns.show', { campaign: campaign.id })"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-500 text-white rounded-md hover:bg-emerald-600 transition-colors mr-2"
                                        title="View campaign details"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <!-- View -->
                                    </Link>

                                    <!-- Delete Button -->
                                    <button style="padding: 8px;"
                                        @click="deleteCampaign(campaign.id)" 
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors"
                                        title="Delete campaign"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <!-- Delete -->
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