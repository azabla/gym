<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkPaymentService
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Process automatic renewals for selected members.
     */
    public function process(Collection $members): array
    {
        $success = 0;
        $failed = 0;
        $skipped = 0;

        $errors = [];

        foreach ($members as $member) {

            try {

                if (! $member instanceof Member) {
                    $skipped++;
                    continue;
                }

                if (! $member->package_id) {

                    $skipped++;

                    $errors[] = [
                        'member_id' => $member->id,
                        'name' => $member->user?->name,
                        'error' => 'No package assigned.',
                    ];

                    continue;
                }

                if (($member->duration_value ?? 0) < 1) {

                    $skipped++;

                    $errors[] = [
                        'member_id' => $member->id,
                        'name' => $member->user?->name,
                        'error' => 'Invalid duration value.',
                    ];

                    continue;
                }

                $this->paymentService
                    ->createMembershipPayment(
                        $member
                    );

                $success++;

            } catch (Throwable $e) {

                $failed++;

                Log::error(
                    'Bulk payment failed',
                    [
                        'member_id' => $member->id,
                        'message' => $e->getMessage(),
                    ]
                );

                $errors[] = [
                    'member_id' => $member->id,
                    'name' => $member->user?->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}