<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Package;
use Carbon\Carbon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        Log::debug('mutateFormDataBeforeFill called in EditUser');

        $user = $this->record;  // Access the record from the page
        if ($user?->member) {
            Log::debug('Member data:', $user->member->toArray());
            $data['member'] = $user->member->toArray();
        }
        Log::debug('Form data after mutate:', $data);
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        Log::debug('Submitted update data: ' . json_encode($data));

        // Update the user
        $record->update($data);

        if (isset($data['member'])) {
            $memberData = $data['member'];
            $memberData['user_id'] = $record->id;

            try {
                // Recalculate dates (default unit 'month' if no package)
                $package = Package::find($memberData['package_id'] ?? null);
                if (!$package) {
                    Log::warning('Package not found for ID: ' . ($memberData['package_id'] ?? 'null') . ' - Using default month unit');
                }
                $durationUnit = $package ? ($package->duration_unit ?? 'month') : 'month';
                $startingDate = $memberData['starting_date'] ?? Carbon::now()->toDateString();
                $duration = (int) ($memberData['duration_value'] ?? 1);

                $from = Carbon::parse($startingDate);
                $until = $from->copy()->add($duration, $durationUnit);

                $memberData['valid_from'] = $from->toDateString();
                $memberData['valid_until'] = $until->toDateString();

                Log::debug('Final member data before update/create: ' . json_encode($memberData));

                if ($record->member) {
                    $record->member->update($memberData);
                } else {
                    $record->member()->create($memberData);
                }
            } catch (\Exception $e) {
                Log::error('Member update/create failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            }
        } elseif ($record->member) {
            try {
                $record->member->delete();
            } catch (\Exception $e) {
                Log::error('Member delete failed: ' . $e->getMessage());
            }
        }

        return $record;
    }
}