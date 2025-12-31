<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Package;
use Carbon\Carbon;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        Log::debug('Submitted creation data: ' . json_encode($data));

        // Create the user
        $user = static::getModel()::create($data);

        if (isset($data['member'])) {
            $memberData = $data['member'];
            $memberData['user_id'] = $user->id;  // Explicitly set

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
                $until = $from->copy()->add($duration, $durationUnit);  // Simplified add using Carbon's add method

                $memberData['valid_from'] = $from->toDateString();
                $memberData['valid_until'] = $until->toDateString();

                Log::debug('Final member data before create: ' . json_encode($memberData));

                $user->member()->create($memberData);
            } catch (\Exception $e) {
                Log::error('Member creation failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                // Optionally notify or rollback, but continue with user creation
            }
        }

        return $user;
    }
}