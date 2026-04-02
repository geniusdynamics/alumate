<template>
    <div class="tax-receipt-manager">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900">Tax Receipts</h3>
                <button
                    @click="generateReceipt"
                    :disabled="generating"
                    class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:bg-gray-400"
                >
                    {{ generating ? 'Generating...' : 'Generate Receipt' }}
                </button>
            </div>

            <div v-if="loading" class="py-8 text-center">
                <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Loading tax receipts...</p>
            </div>

            <div v-else-if="taxReceipts.length === 0" class="py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No tax receipts</h3>
                <p class="mt-1 text-sm text-gray-500">You don't have any tax receipts yet.</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="receipt in taxReceipts" :key="receipt.id" class="rounded-lg border border-gray-200 p-4 transition-shadow hover:shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-lg font-medium text-gray-900">Tax Year {{ receipt.tax_year }}</h4>
                                <span :class="['rounded-full px-2 py-1 text-xs font-medium', getStatusColor(receipt.status)]">
                                    {{ getStatusLabel(receipt.status) }}
                                </span>
                            </div>

                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium">Receipt Number:</span>
                                    {{ receipt.receipt_number }}
                                </div>
                                <div>
                                    <span class="font-medium">Total Amount:</span>
                                    ${{ receipt.total_amount }}
                                </div>
                                <div>
                                    <span class="font-medium">Receipt Date:</span>
                                    {{ formatDate(receipt.receipt_date) }}
                                </div>
                                <div>
                                    <span class="font-medium">Donations:</span>
                                    {{ receipt.donations.length }} donation(s)
                                </div>
                            </div>

                            <div v-if="receipt.sent_at" class="mt-2">
                                <span class="text-sm text-green-600"> <strong>Sent:</strong> {{ formatDate(receipt.sent_at) }} </span>
                            </div>
                        </div>

                        <div class="ml-4 flex space-x-2">
                            <button
                                v-if="receipt.pdf_path"
                                @click="downloadReceipt(receipt)"
                                class="rounded-md bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700 transition-colors hover:bg-blue-200"
                            >
                                Download PDF
                            </button>

                            <button
                                v-if="receipt.status === 'generated' && receipt.pdf_path"
                                @click="resendReceipt(receipt)"
                                :disabled="resendingId === receipt.id"
                                class="rounded-md bg-green-100 px-3 py-1 text-sm font-medium text-green-700 transition-colors hover:bg-green-200 disabled:opacity-50"
                            >
                                {{ resendingId === receipt.id ? 'Sending...' : 'Email Receipt' }}
                            </button>

                            <button
                                @click="viewDetails(receipt)"
                                class="rounded-md bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                            >
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Receipt Modal -->
        <div
            v-if="showingGenerateModal"
            class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
            @click="closeGenerateModal"
        >
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg" @click.stop>
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Generate Tax Receipt</h3>

                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700"> Tax Year </label>
                        <select
                            v-model="selectedTaxYear"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option v-for="year in availableYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button
                            @click="closeGenerateModal"
                            class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmGenerate"
                            :disabled="generating"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ generating ? 'Generating...' : 'Generate Receipt' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

interface TaxReceipt {
    id: number;
    receipt_number: string;
    tax_year: number;
    total_amount: number;
    receipt_date: string;
    status: string;
    pdf_path?: string;
    sent_at?: string;
    donations: Array<{
        donation_id: number;
        amount: number;
        date: string;
        campaign: string;
    }>;
}

const emit = defineEmits<{
    receiptGenerated: [receipt: TaxReceipt];
}>();

const loading = ref(true);
const generating = ref(false);
const resendingId = ref<number | null>(null);
const taxReceipts = ref<TaxReceipt[]>([]);
const showingGenerateModal = ref(false);
const selectedTaxYear = ref(new Date().getFullYear() - 1);

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let year = currentYear; year >= currentYear - 5; year--) {
        years.push(year);
    }
    return years;
});

onMounted(() => {
    loadTaxReceipts();
});

async function loadTaxReceipts() {
    try {
        const response = await fetch('/api/user/tax-receipts');
        const data = await response.json();
        taxReceipts.value = data.data || [];
    } catch (error) {
        console.error('Failed to load tax receipts:', error);
    } finally {
        loading.value = false;
    }
}

function generateReceipt() {
    showingGenerateModal.value = true;
}

function closeGenerateModal() {
    showingGenerateModal.value = false;
}

async function confirmGenerate() {
    generating.value = true;

    try {
        const response = await fetch('/api/tax-receipts/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                donor_id: getCurrentUserId(), // You'll need to implement this
                tax_year: selectedTaxYear.value,
            }),
        });

        if (response.ok) {
            const result = await response.json();
            taxReceipts.value.unshift(result.receipt);
            emit('receiptGenerated', result.receipt);
            closeGenerateModal();
            // TODO-toast: alert('Tax receipt generated successfully!');
        } else {
            const error = await response.json();
            // TODO-toast: alert(error.message || 'Failed to generate tax receipt');
        }
    } catch (error) {
        console.error('Failed to generate receipt:', error);
        // TODO-toast: alert('Failed to generate tax receipt');
    } finally {
        generating.value = false;
    }
}

async function downloadReceipt(receipt: TaxReceipt) {
    try {
        const response = await fetch(`/api/tax-receipts/${receipt.id}/download`);

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Tax_Receipt_${receipt.tax_year}_${receipt.receipt_number}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            // TODO-toast: alert('Failed to download tax receipt');
        }
    } catch (error) {
        console.error('Failed to download receipt:', error);
        // TODO-toast: alert('Failed to download tax receipt');
    }
}

async function resendReceipt(receipt: TaxReceipt) {
    resendingId.value = receipt.id;

    try {
        const response = await fetch(`/api/tax-receipts/${receipt.id}/resend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            // TODO-toast: alert('Tax receipt email sent successfully!');
            // Update the receipt status
            const index = taxReceipts.value.findIndex((r) => r.id === receipt.id);
            if (index !== -1) {
                taxReceipts.value[index].status = 'sent';
                taxReceipts.value[index].sent_at = new Date().toISOString();
            }
        } else {
            // TODO-toast: alert('Failed to send tax receipt email');
        }
    } catch (error) {
        console.error('Failed to resend receipt:', error);
        // TODO-toast: alert('Failed to send tax receipt email');
    } finally {
        resendingId.value = null;
    }
}

function viewDetails(receipt: TaxReceipt) {
    // Navigate to receipt details page or show modal
    window.location.href = `/tax-receipts/${receipt.id}`;
}

function getStatusColor(status: string): string {
    switch (status) {
        case 'generated':
            return 'bg-blue-100 text-blue-800';
        case 'sent':
            return 'bg-green-100 text-green-800';
        case 'downloaded':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status: string): string {
    switch (status) {
        case 'generated':
            return 'Generated';
        case 'sent':
            return 'Sent';
        case 'downloaded':
            return 'Downloaded';
        default:
            return status.charAt(0).toUpperCase() + status.slice(1);
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function getCurrentUserId(): number {
    // This should be implemented to get the current user's ID
    // You might get this from a global store, props, or API call
    return 1; // Placeholder
}
</script>
