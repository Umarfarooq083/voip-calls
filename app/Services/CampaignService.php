<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Extension;
use App\Models\VoiceMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function create(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            $campaign = Campaign::create([
                'name' => $data['name'],
                'ivr_id' => $data['ivr_id'],
                'ivr_name' => $data['ivr_name'],
                'voice_message_id' => $data['voice_message_id'] ?? null,
                'status' => 'pending',
                'total_contacts' => 0,
                'called_contacts' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
            ]);

            if (isset($data['csv_file'])) {
                $this->importContactsFromCsv($campaign, $data['csv_file']);
            }

            return $campaign;
        });
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data) {
            $updateData = array_filter($data, function ($key) {
                return $key !== 'csv_file';
            }, ARRAY_FILTER_USE_KEY);

            if (isset($data['csv_file'])) {
                $this->importContactsFromCsv($campaign, $data['csv_file']);
            }

            $campaign->update($updateData);

            return $campaign->fresh();
        });
    }

    public function delete(Campaign $campaign): bool
    {
        return DB::transaction(function () use ($campaign) {
            $campaign->contacts()->delete();

            return $campaign->delete();
        });
    }

    public function list(array $filters = [])
    {
        $query = Campaign::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%");
            });
        }

        return $query->with(['extension', 'contacts'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function find(int $id): ?Campaign
    {
        return Campaign::with(['extension', 'voiceMessage', 'contacts'])->find($id);
    }

    public function importContactsFromCsv(Campaign $campaign, $file): void
    {
        CampaignContact::where('campaign_id', $campaign->id)->delete();
        $path = $file->getRealPath();
        $fileHandle = fopen($path, 'r');

        $rowCount = 0;
        $header = null;

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
            if ($header === null) {
                $header = $row;
                continue;
            }

            $rowCount++;

            $customerName = '';
            $phoneNumber = '';

            foreach ($header as $index => $columnName) {
                $columnName = strtolower(trim($columnName));
                if ($columnName === 'customer_name') {
                    $customerName = trim($row[$index] ?? '');
                }
                if ($columnName === 'phone_number') {
                    $phoneNumber = trim($row[$index] ?? '');
                }
            }

            if (!empty($phoneNumber)) {
                CampaignContact::create([
                    'campaign_id' => $campaign->id,
                    'customer_name' => $customerName,
                    'phone_number' => $phoneNumber,
                    'status' => 'pending',
                ]);
            }
        }

        fclose($fileHandle);

        $campaign->update([
            'total_contacts' => $rowCount,
        ]);
    }

    public function getVoiceMessages()
    {
        return VoiceMessage::where('is_active', true)->get();
    }

    public function getExtensions()
    {
        return Extension::where('is_active', true)->get();
    }
}