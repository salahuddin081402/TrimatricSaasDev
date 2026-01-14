<?php
// app/Jobs/Communication/SendBatchMessageToRecipientJob.php

namespace App\Jobs\Communication;

use App\Utility\SendMailUtility;
use App\Utility\SendSMSUtility;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendBatchMessageToRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $timeout = 120;
    public int $tries = 2;

    public function __construct(
        public int $companyId,
        public string $batchNo,
        public int $userId,
        public string $recipientName,
        public string $recipientPhone,
        public string $recipientEmail
    ) {
        // IMPORTANT: do NOT declare $queue property (Queueable handles it)
        $this->onQueue('batch_delivery');
    }

    public function handle(): void
    {
        // If cancelled from Laravel batch UI / cancel API
        if ($this->batch()?->cancelled()) {
            return;
        }

        $batch = DB::table('communication_batches')
            ->where('company_id', $this->companyId)
            ->where('batch_no', $this->batchNo)
            ->first();

        if (!$batch) return;

        // Hard stop (your table-driven cancel)
        if ((int)($batch->is_cancel_requested ?? 0) === 1 || ($batch->status ?? '') === 'CANCELLED') {
            return;
        }

        // Company branding for email template (name, contact, logo).
        // Email clients need an ABSOLUTE URL for images; relative URLs won't render.
        $companyName = null;
        $companyContact = null;
        $companyLogoUrl = null;

        $company = DB::table('companies')
            ->where('id', $this->companyId)
            ->first(['name', 'contact_no', 'logo']);

        if ($company) {
            $companyName = $company->name ?? null;
            $companyContact = $company->contact_no ?? null;

            $logo = (string) ($company->logo ?? '');
            if ($logo !== '') {
                if (preg_match('/^https?:\/\//i', $logo)) {
                    $companyLogoUrl = $logo;
                } else {
                    $base = rtrim((string) config('app.url'), '/');
                    $companyLogoUrl = $base . '/' . ltrim($logo, '/');
                }
            }
        }

        $sendSms = (bool) ($batch->send_sms ?? false);
        $sendEmail = (bool) ($batch->send_email ?? false);

        $subject = (string) ($batch->subject ?? '');
        $message = (string) ($batch->message ?? '');
        $extra = (string) ($batch->extra_message ?? '');

        $okAll = true;
        $anyAttempt = false;

        // Token replacement
        $baseText = $this->applyTokens($message, (string)($batch->batch_no ?? $this->batchNo));
        $baseExtra = $this->applyTokens($extra, (string)($batch->batch_no ?? $this->batchNo));
        $baseSubject = $this->applyTokens($subject, (string)($batch->batch_no ?? $this->batchNo));

        // SMS
        if ($sendSms) {
            $anyAttempt = true;

            if (trim($this->recipientPhone) === '') {
                $okAll = false;
                $this->insertLog('SMS', 'FAILED', 'Missing phone number', 'MRAM', null, $baseSubject, $baseText);
            } else {
                $resp = null;
                $status = 'SUCCESS';
                $failReason = null;

                try {
                    $resp = SendSMSUtility::sendSMS($this->recipientPhone, $baseText);
                    if ($resp === null || trim((string)$resp) === '') {
                        $status = 'FAILED';
                        $failReason = 'Empty SMS gateway response';
                    }
                } catch (\Throwable $e) {
                    $status = 'FAILED';
                    $failReason = 'SMS exception: ' . $e->getMessage();
                }

                if ($status === 'FAILED') $okAll = false;
                $this->insertLog('SMS', $status, $failReason, 'MRAM', $resp, $baseSubject, $baseText);
            }
        }

        // EMAIL
        if ($sendEmail) {
            $anyAttempt = true;

            if (trim($this->recipientEmail) === '') {
                $okAll = false;
                $this->insertLog('EMAIL', 'FAILED', 'Missing email address', 'SMTP', null, $baseSubject, $baseText);
            } else {
                $status = 'SUCCESS';
                $failReason = null;

                try {
                    $view = 'mail.general';
                    $data = [
                        'company_logo' => $companyLogoUrl,
                        'company_name' => $companyName,
                        'company_contact' => $companyContact,
                        'subject' => $baseSubject,
                        'recipient_name' => $this->recipientName ?: 'User',
                        'message' => $baseText,
                        'extra_message' => $baseExtra,
                    ];

                    $r = SendMailUtility::sendMail($this->recipientEmail, $baseSubject, $view, $data);
                    if ((int)$r !== 1) {
                        $status = 'FAILED';
                        $failReason = 'Mail send failed (utility returned 0)';
                    }
                } catch (\Throwable $e) {
                    $status = 'FAILED';
                    $failReason = 'Mail exception: ' . $e->getMessage();
                }

                if ($status === 'FAILED') $okAll = false;
                $this->insertLog('EMAIL', $status, $failReason, 'SMTP', null, $baseSubject, $baseText);
            }
        }

        if (!$anyAttempt) {
            return;
        }

        // Batch counters (recipient-level)
        DB::table('communication_batches')
            ->where('company_id', $this->companyId)
            ->where('batch_no', $this->batchNo)
            ->update([
                'processed_count' => DB::raw('processed_count + 1'),
                'success_count' => DB::raw('success_count + ' . ($okAll ? '1' : '0')),
                'failed_count' => DB::raw('failed_count + ' . ($okAll ? '0' : '1')),
                'updated_at' => now(),
            ]);
    }

    private function insertLog(
        string $type,
        string $status,
        ?string $failureReason,
        ?string $gatewayName,
        $gatewayResponse,
        ?string $subject,
        ?string $body
    ): void {
        $resp = null;
        if ($gatewayResponse !== null) {
            $resp = (string) $gatewayResponse;
            if (strlen($resp) > 5000) $resp = substr($resp, 0, 5000);
        }

        DB::table('communication_message_logs')->insert([
            'company_id' => $this->companyId,
            'batch_no' => $this->batchNo,
            'message_type' => $type,
            'recipient_name' => $this->recipientName ?: null,
            'recipient_phone' => $type === 'SMS' ? ($this->recipientPhone ?: null) : null,
            'recipient_email' => $type === 'EMAIL' ? ($this->recipientEmail ?: null) : null,
            'message_subject' => $subject ?: null,
            'message_body' => $body ?: null,
            'status' => $status,
            'failure_reason' => $failureReason,
            'gateway_name' => $gatewayName,
            'gateway_response' => $resp,
            'sent_at' => now(),
            'sender_ip' => request()?->ip(),
            'created_by' => $this->batchCreatedBy(),
            'created_at' => now(),
        ]);
    }

    private function batchCreatedBy(): ?int
    {
        try {
            $b = DB::table('communication_batches')
                ->where('company_id', $this->companyId)
                ->where('batch_no', $this->batchNo)
                ->first(['created_by']);
            return $b?->created_by ? (int)$b->created_by : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyTokens(string $text, string $batchNo): string
    {
        if ($text === '') return '';

        $repl = [
            '{FULL_NAME}' => $this->recipientName,
            '{PHONE}' => $this->recipientPhone,
            '{EMAIL}' => $this->recipientEmail,
            '{BATCH_NO}' => $batchNo,
        ];

        return str_replace(array_keys($repl), array_values($repl), $text);
    }
}
