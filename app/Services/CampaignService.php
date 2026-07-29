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
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        return DB::transaction(function () use ($data) {

            $campaign = Campaign::create([
                'name' => $data['name'],
                'ivr_id' => $data['ivr_id'],
                'ivr_name' => $data['ivr_name'],
                'trunk_channalId' => $data['trunk_channalId'],
                'no_of_calls' => $data['no_of_calls'],
                'voice_message_id' => $data['voice_message_id'] ?? null,
                'status' => 'pending',
                'total_contacts' => 0,
                'called_contacts' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
            ]);

            if (!empty($data['csv_file'])) {
                $this->importContactsFromCsv($campaign, $data['csv_file']);
            }

            return $campaign;
        });
    }


    public function update(Campaign $campaign, array $data): Campaign
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
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

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function find(int $id): ?Campaign
    {
        return Campaign::with(['extension', 'voiceMessage', 'contacts'])->find($id);
    }

    public function importContactsFromCsv(Campaign $campaign, $file): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        CampaignContact::where('campaign_id', $campaign->id)->delete();
    
        $path = $file->getRealPath();
        $fileHandle = fopen($path, 'r');

        $header = null;
        $rowCount = 0;

        $chunk = [];
        $chunkSize = 500;

        while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {

            if ($header === null) {
                $header = array_map(fn($value) => strtolower(trim($value)), $row);
                continue;
            }
            $customerName = '';
            $phoneNumber = '';
            foreach ($header as $index => $columnName) {

                if ($columnName == 'customer_name') {
                    $customerName = trim($row[$index] ?? '');
                }
                if ($columnName == 'phone_number') {
                    $phoneNumber = trim($row[$index] ?? '');
                }
            }

            if (empty($phoneNumber)) {
                continue;
            }

            $chunk[] = [
                'campaign_id' => $campaign->id,
                'customer_name' => $customerName,
                'phone_number' => $phoneNumber,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rowCount++;

            if (count($chunk) >= $chunkSize) {
                CampaignContact::insert($chunk);
                $chunk = [];
            }
        }
        // Remaining Records
        if (!empty($chunk)) {
            CampaignContact::insert($chunk);
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