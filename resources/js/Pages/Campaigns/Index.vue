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
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                    <button v-if="campaign.status === 'pending' || campaign.status === 'in_progress'" @click="startCalling(campaign.id)" class="hover:underline mr-4">
                                        Start Calling
                                    </button>
                                    <button  @click="retryFailedCalls(campaign.id)" class="hover:underline mr-4 text-orange-600">
                                        Retry Failed Calls
                                    </button>
                                    <Link :href="route('campaigns.edit', { campaign: campaign.id })"
                                        class="hover:underline mr-4">
                                        Edit
                                    </Link>
                                    <Link :href="route('campaigns.show', { campaign: campaign.id })"
                                        class="hover:underline mr-4">
                                        View
                                    </Link>
                                    <button @click="deleteCampaign(campaign.id)" class="hover:underline text-red-600">
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