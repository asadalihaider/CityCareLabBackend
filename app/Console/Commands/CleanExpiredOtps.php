<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;

class CleanExpiredOtps extends Command
{
    protected $signature = 'otp:clean-expired';
    protected $description = 'Clean expired OTPs from the database';

    public function handle(OtpService $otpService): int
    {
        $this->info('Cleaning expired OTPs...');
        
        $deletedCount = $otpService->cleanExpiredOtps();
        
        $this->info("Deleted {$deletedCount} expired OTPs.");
        
        return Command::SUCCESS;
    }
}
