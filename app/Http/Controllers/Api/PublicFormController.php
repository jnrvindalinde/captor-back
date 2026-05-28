<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationReceivedMail;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\OrgInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class PublicFormController extends Controller
{
    /**
     * Contact page form submission.
     * POST /api/public/contact
     */
    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:200'],
            'topic'   => ['required', Rule::in(['applications', 'advising', 'partnerships', 'press', 'other'])],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $lead = DB::transaction(function () use ($data) {
            $lead = Lead::create([
                'kind'   => Lead::KIND_CONTACT,
                'status' => 'new',
                'name'   => $data['name'],
                'email'  => $data['email'],
                'source' => 'contact-page',
            ]);

            ContactMessage::create([
                'lead_id' => $lead->id,
                'topic'   => $data['topic'],
                'message' => $data['message'],
            ]);

            return $lead;
        });

        return response()->json(['id' => $lead->id, 'status' => 'received'], 201);
    }

    /**
     * Organization inquiry modal submission.
     * POST /api/public/org-inquiry
     */
    public function orgInquiry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'role'         => ['required', 'string', 'max:120'],
            'organization' => ['required', 'string', 'max:200'],
            'about'        => ['required', 'string', 'max:5000'],
            'contact'      => ['required', 'string', 'max:200'],
        ]);

        // Classify contact as email or phone.
        $contactKind = filter_var($data['contact'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : (preg_match('/\d{7,}/', preg_replace('/\D/', '', $data['contact'])) ? 'phone' : null);

        if (! $contactKind) {
            return response()->json([
                'message' => 'Please enter a valid email or phone number.',
                'errors'  => ['contact' => ['Please enter a valid email or phone number.']],
            ], 422);
        }

        $lead = DB::transaction(function () use ($data, $contactKind) {
            $lead = Lead::create([
                'kind'   => Lead::KIND_ORG,
                'status' => 'new',
                'name'   => $data['name'],
                'email'  => $contactKind === 'email' ? $data['contact'] : null,
                'phone'  => $contactKind === 'phone' ? $data['contact'] : null,
                'source' => 'org-inquiry-modal',
            ]);

            OrgInquiry::create([
                'lead_id'       => $lead->id,
                'about'         => $data['about'],
                'role'          => $data['role'],
                'organization'  => $data['organization'],
                'contact_kind'  => $contactKind,
                'contact_value' => $data['contact'],
            ]);

            return $lead;
        });

        return response()->json(['id' => $lead->id, 'status' => 'received'], 201);
    }

    /**
     * Individual application submission (multipart for file uploads).
     * POST /api/public/applications
     */
    public function application(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'email'        => ['required', 'email', 'max:200'],
            'phone'        => ['nullable', 'string', 'max:60'],
            'source'       => ['nullable', Rule::in(['referral', 'linkedin', 'google', 'event', 'other'])],
            'newsletter'   => ['sometimes', 'boolean'],

            'status_self'  => ['required', Rule::in(['student-final', 'graduate-recent', 'professional', 'senior', 'other'])],
            'status_other' => ['nullable', 'string', 'max:200'],
            'location'     => ['required', 'string', 'max:200'],
            'field'        => ['required', 'string', 'max:200'],

            'goal'         => ['required', Rule::in(['study-abroad', 'local-job', 'international-placement', 'pivot', 'postgrad-gh', 'other'])],
            'goal_other'   => ['nullable', 'string', 'max:200'],
            'targets'      => ['nullable', 'array'],
            'targets.*'    => ['string', 'max:120'],
            'timeline'     => ['required', Rule::in(['0-3', '3-6', '6-12', '12+'])],
            'budget'       => ['required', Rule::in(['self', 'scholarship', 'employer', 'unsure'])],

            'story'        => ['nullable', 'string', 'max:10000'],

            'files'        => ['nullable', 'array', 'max:10'],
            'files.*'      => ['file', 'max:10240'], // 10 MB each
        ]);

        $application = DB::transaction(function () use ($data, $request) {
            $lead = Lead::create([
                'kind'   => Lead::KIND_APPLICATION,
                'status' => 'new',
                'name'   => $data['name'],
                'email'  => $data['email'],
                'phone'  => $data['phone'] ?? null,
                'source' => $data['source'] ?? null,
            ]);

            $application = Application::create([
                'lead_id'      => $lead->id,
                'status_self'  => $data['status_self'],
                'status_other' => $data['status_other'] ?? null,
                'location'     => $data['location'],
                'field'        => $data['field'],
                'goal'         => $data['goal'],
                'goal_other'   => $data['goal_other'] ?? null,
                'targets'      => $data['targets'] ?? [],
                'timeline'     => $data['timeline'],
                'budget'       => $data['budget'],
                'story'        => $data['story'] ?? null,
                'newsletter'   => (bool) ($data['newsletter'] ?? false),
            ]);

            foreach ($request->file('files', []) as $upload) {
                $path = $upload->store("applications/{$application->id}");
                ApplicationFile::create([
                    'application_id' => $application->id,
                    'original_name'  => $upload->getClientOriginalName(),
                    'mime'           => $upload->getClientMimeType(),
                    'size'           => $upload->getSize(),
                    'path'           => $path,
                ]);
            }

            return $application;
        });

        // Best-effort applicant confirmation. Configure mail driver (e.g. Resend)
        // via MAIL_MAILER + RESEND_API_KEY in .env for live delivery.
        try {
            Mail::to($application->lead->email)->send(new ApplicationReceivedMail($application->lead));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'id'      => $application->id,
            'lead_id' => $application->lead_id,
            'status'  => 'received',
        ], 201);
    }
}
