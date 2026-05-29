<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Note;
use App\Models\OrgInquiry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Mirrors the frontend mock dataset in `captor-front/src/app/admin/_mock.ts`
 * so the admin UI can be wired progressively without losing parity.
 *
 * Run with:
 *   php artisan db:seed --class=AdminDemoSeeder
 */
class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@local.com'],
            [
                'name'     => 'Local Admin',
                'password' => bcrypt('password'),
                'role'     => 'super_admin',
            ],
        );

        $advisor = User::firstOrCreate(
            ['email' => 'akua@career360consult.com'],
            [
                'name'     => 'Akua Mensah',
                'password' => bcrypt('password'),
                'role'     => 'admin',
            ],
        );

        // ----- 1. Application — Derek (new, pending) -----------------------
        $derek = Lead::create([
            'uuid'         => '01a4a1de-0001-4ad0-8e2f-0a1b2c3d4e01',
            'kind'         => Lead::KIND_APPLICATION,
            'status'       => 'new',
            'name'         => 'Derek Osei',
            'email'        => 'derek.osei@example.com',
            'phone'        => '+233 24 412 3456',
            'source'       => 'linkedin',
            'tags'         => ['UK', 'PhD'],
            'created_at'   => Carbon::parse('2026-05-27T09:12:00Z'),
            'updated_at'   => Carbon::parse('2026-05-27T09:12:00Z'),
        ]);
        $derekApp = Application::create([
            'lead_id'      => $derek->id,
            'status_self'  => 'graduate-recent',
            'location'     => 'Accra, Ghana',
            'field'        => 'Computer Science',
            'goal'         => 'study-abroad',
            'targets'      => ['United Kingdom', 'Germany', 'Netherlands'],
            'timeline'     => '3-6',
            'budget'       => 'scholarship',
            'story'        => 'Two years out of university, currently a backend engineer at a fintech. Looking at applied ML masters with funding.',
            'newsletter'   => true,
            'decision'     => Application::DECISION_PENDING,
        ]);
        ApplicationFile::create([
            'application_id' => $derekApp->id,
            'original_name'  => 'CV-Derek-Osei.pdf',
            'mime'           => 'application/pdf',
            'size'           => 184320,
            'path'           => 'applications/101/cv.pdf',
        ]);
        ApplicationFile::create([
            'application_id' => $derekApp->id,
            'original_name'  => 'transcript.pdf',
            'mime'           => 'application/pdf',
            'size'           => 422912,
            'path'           => 'applications/101/transcript.pdf',
        ]);
        Note::create([
            'lead_id'    => $derek->id,
            'author_id'  => $admin->id,
            'kind'       => Note::KIND_SYSTEM,
            'body'       => 'Application received via the website.',
            'created_at' => Carbon::parse('2026-05-27T08:12:00Z'),
            'updated_at' => Carbon::parse('2026-05-27T08:12:00Z'),
        ]);
        Note::create([
            'lead_id'    => $derek->id,
            'author_id'  => $advisor->id,
            'kind'       => Note::KIND_MANUAL,
            'body'       => 'Strong CS background — worth a discovery call once approved.',
            'created_at' => Carbon::parse('2026-05-27T09:05:00Z'),
            'updated_at' => Carbon::parse('2026-05-27T09:05:00Z'),
        ]);

        // ----- 2. Org inquiry — Bright Labs (contacted) --------------------
        $bright = Lead::create([
            'uuid'             => '01a4a1de-0002-4ad0-8e2f-0a1b2c3d4e02',
            'kind'             => Lead::KIND_ORG,
            'status'           => 'contacted',
            'assigned_user_id' => $advisor->id,
            'name'             => 'Naa Densua',
            'email'            => 'naa@brightlabs.gh',
            'phone'            => null,
            'source'           => 'referral',
            'tags'             => ['org', 'engineering'],
            'created_at'       => Carbon::parse('2026-05-27T06:30:00Z'),
            'updated_at'       => Carbon::parse('2026-05-27T08:00:00Z'),
        ]);
        OrgInquiry::create([
            'lead_id'       => $bright->id,
            'about'         => "We're a 24-person engineering team. Onboarding the last six hires has been rough — looking for structured advising for new engineers in their first 90 days.",
            'role'          => 'Head of Engineering',
            'organization'  => 'Bright Labs',
            'contact_kind'  => 'email',
            'contact_value' => 'naa@brightlabs.gh',
        ]);
        Note::create([
            'lead_id'   => $bright->id,
            'author_id' => $advisor->id,
            'kind'      => Note::KIND_MANUAL,
            'body'      => 'Replied with an intro deck and the standard discovery questions.',
            'created_at'=> Carbon::parse('2026-05-27T07:00:00Z'),
            'updated_at'=> Carbon::parse('2026-05-27T07:00:00Z'),
        ]);
        Note::create([
            'lead_id'   => $bright->id,
            'author_id' => $advisor->id,
            'kind'      => Note::KIND_MANUAL,
            'body'      => 'Asked for org headcount + current L&D budget shape.',
            'created_at'=> Carbon::parse('2026-05-27T08:00:00Z'),
            'updated_at'=> Carbon::parse('2026-05-27T08:00:00Z'),
        ]);

        // ----- 3. Contact message — Esi (scheduled) ------------------------
        $esi = Lead::create([
            'uuid'             => '01a4a1de-0003-4ad0-8e2f-0a1b2c3d4e03',
            'kind'             => Lead::KIND_CONTACT,
            'status'           => 'scheduled',
            'assigned_user_id' => $admin->id,
            'name'             => 'Esi Boateng',
            'email'            => 'esi.boateng@example.com',
            'phone'            => '+233 20 765 1122',
            'source'           => 'instagram',
            'scheduled_at'     => Carbon::parse('2026-05-30T15:00:00Z'),
            'tags'             => ['pivot'],
            'created_at'       => Carbon::parse('2026-05-26T09:30:00Z'),
            'updated_at'       => Carbon::parse('2026-05-26T10:00:00Z'),
        ]);
        ContactMessage::create([
            'lead_id' => $esi->id,
            'topic'   => 'advising',
            'message' => "Mid-career switch from accounting into product management. I'd like to talk through how realistic a 6-month runway is.",
        ]);
        Note::create([
            'lead_id'   => $esi->id,
            'author_id' => $admin->id,
            'kind'      => Note::KIND_MANUAL,
            'body'      => 'Called twice, sent Google Calendar invite for the 30-min discovery slot on Saturday.',
            'created_at'=> Carbon::parse('2026-05-26T09:42:00Z'),
            'updated_at'=> Carbon::parse('2026-05-26T09:42:00Z'),
        ]);
        Meeting::create([
            'lead_id'         => $esi->id,
            'scheduled_by'    => $admin->id,
            'scheduled_at'    => Carbon::parse('2026-05-30T15:00:00Z'),
            'google_event_id' => 'gcal_a1B2c3D4e5F6g7H8',
            'google_meet_link'=> 'https://meet.google.com/kya-rfvb-zpe',
            'status'          => 'scheduled',
        ]);

        // ----- 4. Application — Kojo (qualified, approved) -----------------
        $kojo = Lead::create([
            'uuid'             => '01a4a1de-0004-4ad0-8e2f-0a1b2c3d4e04',
            'kind'             => Lead::KIND_APPLICATION,
            'status'           => 'qualified',
            'assigned_user_id' => $advisor->id,
            'name'             => 'Kojo Asare',
            'email'            => 'kojo.asare@example.com',
            'phone'            => '+233 27 998 4421',
            'source'           => 'referral',
            'tags'             => ['MBA'],
            'created_at'       => Carbon::parse('2026-05-25T08:00:00Z'),
            'updated_at'       => Carbon::parse('2026-05-26T16:00:00Z'),
        ]);
        $kojoApp = Application::create([
            'lead_id'      => $kojo->id,
            'status_self'  => 'professional',
            'location'     => 'Kumasi, Ghana',
            'field'        => 'Mechanical Engineering',
            'goal'         => 'study-abroad',
            'targets'      => ['Canada', 'United States'],
            'timeline'     => '6-12',
            'budget'       => 'scholarship',
            'story'        => 'Five years in oil & gas mechanical roles. Pivoting toward renewables / clean energy research masters.',
            'newsletter'   => false,
            'decision'     => Application::DECISION_APPROVED,
            'decision_note'=> 'Approved for advisory. Schedule mock interview next.',
            'decided_at'   => Carbon::parse('2026-05-25T10:00:00Z'),
            'decided_by'   => $advisor->id,
        ]);
        ApplicationFile::create([
            'application_id' => $kojoApp->id,
            'original_name'  => 'kojo-cv.pdf',
            'mime'           => 'application/pdf',
            'size'           => 198432,
            'path'           => 'applications/104/cv.pdf',
        ]);
        Meeting::create([
            'lead_id'         => $kojo->id,
            'scheduled_by'    => $advisor->id,
            'scheduled_at'    => Carbon::parse('2026-06-02T10:30:00Z'),
            'google_event_id' => 'gcal_q9R8s7T6u5V4w3X2',
            'google_meet_link'=> 'https://meet.google.com/jhd-cqps-mvr',
            'status'          => 'scheduled',
            'notes'           => 'Mock interview + essay review.',
        ]);
        foreach (
            [
                ['Strong fit. GMAT 720, two scholarship-friendly schools shortlisted.', '2026-05-25T10:00:00Z'],
                ['Drafted recommender list together. Two confirmed, one pending.', '2026-05-26T11:00:00Z'],
                ['Sent UWaterloo MASc track materials.', '2026-05-26T16:00:00Z'],
            ] as [$body, $at]
        ) {
            Note::create([
                'lead_id'   => $kojo->id,
                'author_id' => $advisor->id,
                'kind'      => Note::KIND_MANUAL,
                'body'      => $body,
                'created_at'=> Carbon::parse($at),
                'updated_at'=> Carbon::parse($at),
            ]);
        }

        // ----- 5. Application — Ama (won) ---------------------------------
        $ama = Lead::create([
            'uuid'             => '01a4a1de-0005-4ad0-8e2f-0a1b2c3d4e05',
            'kind'             => Lead::KIND_APPLICATION,
            'status'           => 'won',
            'assigned_user_id' => $admin->id,
            'name'             => 'Ama Owusu',
            'email'            => 'ama.owusu@example.com',
            'phone'            => '+233 24 555 7788',
            'source'           => 'web',
            'tags'             => ['INSEAD', 'sponsored'],
            'created_at'       => Carbon::parse('2026-05-12T08:00:00Z'),
            'updated_at'       => Carbon::parse('2026-05-24T17:22:00Z'),
        ]);
        Application::create([
            'lead_id'      => $ama->id,
            'status_self'  => 'professional',
            'location'     => 'Accra, Ghana',
            'field'        => 'Banking',
            'goal'         => 'study-abroad',
            'targets'      => ['France', 'Singapore'],
            'timeline'     => '0-3',
            'budget'       => 'employer',
            'story'        => 'Sponsored by current employer. Decision finalised — INSEAD intake.',
            'newsletter'   => true,
            'decision'     => Application::DECISION_APPROVED,
            'decided_at'   => Carbon::parse('2026-05-12T09:00:00Z'),
            'decided_by'   => $admin->id,
        ]);
        Note::create([
            'lead_id'   => $ama->id,
            'author_id' => $admin->id,
            'kind'      => Note::KIND_MANUAL,
            'body'      => 'INSEAD MBA offer signed. Onboarding into alumni cohort.',
            'created_at'=> Carbon::parse('2026-05-24T17:22:00Z'),
            'updated_at'=> Carbon::parse('2026-05-24T17:22:00Z'),
        ]);

        // ----- 6. Contact — Mawuli (lost / archived) ----------------------
        $mawuli = Lead::create([
            'uuid'       => '01a4a1de-0006-4ad0-8e2f-0a1b2c3d4e06',
            'kind'       => Lead::KIND_CONTACT,
            'status'     => 'lost',
            'name'       => 'Mawuli Ayisi',
            'email'      => 'mawuli@example.com',
            'phone'      => null,
            'source'     => 'web',
            'tags'       => [],
            'created_at' => Carbon::parse('2026-04-21T12:00:00Z'),
            'updated_at' => Carbon::parse('2026-05-05T08:00:00Z'),
        ]);
        ContactMessage::create([
            'lead_id' => $mawuli->id,
            'topic'   => 'other',
            'message' => 'Hi, do you do CV reviews?',
        ]);
        Note::create([
            'lead_id'   => $mawuli->id,
            'author_id' => $admin->id,
            'kind'      => Note::KIND_MANUAL,
            'body'      => 'No response after three follow-ups over two weeks. Marking lost.',
            'created_at'=> Carbon::parse('2026-05-05T08:00:00Z'),
            'updated_at'=> Carbon::parse('2026-05-05T08:00:00Z'),
        ]);

        // ----- Clients -----------------------------------------------------
        $clients = [
            [
                'uuid'    => '01a4b2cf-0001-4ad0-9f2f-0a1b2c3d4e01',
                'name'    => 'Ama Owusu',
                'email'   => 'ama@example.com',
                'phone'   => null,
                'program' => 'study-abroad',
                'consultant_id' => $admin->id,
                'status'  => 'active',
                'start_date'            => Carbon::parse('2026-05-25T00:00:00Z'),
                'next_milestone_label'  => 'SOP first draft',
                'next_milestone_due_at' => Carbon::parse('2026-06-05T17:00:00Z'),
                'satisfaction'          => 5,
                'source_lead_id'        => null,
                'created_at'            => Carbon::parse('2026-05-25T10:00:00Z'),
                'updated_at'            => Carbon::parse('2026-05-28T14:22:00Z'),
            ],
            [
                'uuid'    => '01a4b2cf-0002-4ad0-9f2f-0a1b2c3d4e02',
                'name'    => 'Yaw Boakye',
                'email'   => 'yaw@example.com',
                'phone'   => '+233 26 778 1212',
                'program' => 'scholarship',
                'consultant_id' => $advisor->id,
                'status'  => 'onboarding',
                'start_date'            => Carbon::parse('2026-05-28T00:00:00Z'),
                'next_milestone_label'  => 'Intake call',
                'next_milestone_due_at' => Carbon::parse('2026-06-01T10:00:00Z'),
                'satisfaction'          => null,
                'source_lead_id'        => null,
                'created_at'            => Carbon::parse('2026-05-28T09:30:00Z'),
                'updated_at'            => Carbon::parse('2026-05-28T09:30:00Z'),
            ],
            [
                'uuid'    => '01a4b2cf-0003-4ad0-9f2f-0a1b2c3d4e03',
                'name'    => 'BrightLabs Academy',
                'email'   => 'naa@brightlabs.gh',
                'phone'   => null,
                'program' => 'org-partnership',
                'consultant_id' => $advisor->id,
                'status'  => 'active',
                'start_date'            => Carbon::parse('2026-04-10T00:00:00Z'),
                'next_milestone_label'  => 'Q2 cohort kickoff',
                'next_milestone_due_at' => Carbon::parse('2026-06-10T09:00:00Z'),
                'satisfaction'          => 4,
                'source_lead_id'        => null,
                'created_at'            => Carbon::parse('2026-04-10T09:00:00Z'),
                'updated_at'            => Carbon::parse('2026-05-22T11:10:00Z'),
            ],
            [
                'uuid'    => '01a4b2cf-0004-4ad0-9f2f-0a1b2c3d4e04',
                'name'    => 'Selasi Komla',
                'email'   => 'selasi@example.com',
                'phone'   => null,
                'program' => 'career-coaching',
                'consultant_id' => $admin->id,
                'status'  => 'on_hold',
                'start_date'            => Carbon::parse('2026-03-15T00:00:00Z'),
                'next_milestone_label'  => 'Resume mock interview',
                'next_milestone_due_at' => Carbon::parse('2026-06-12T15:00:00Z'),
                'satisfaction'          => 4,
                'source_lead_id'        => null,
                'created_at'            => Carbon::parse('2026-03-15T08:00:00Z'),
                'updated_at'            => Carbon::parse('2026-05-18T10:00:00Z'),
            ],
            [
                'uuid'    => '01a4b2cf-0005-4ad0-9f2f-0a1b2c3d4e05',
                'name'    => 'Kwesi Mensah',
                'email'   => 'kwesi@example.com',
                'phone'   => '+233 24 119 4400',
                'program' => 'test-prep',
                'consultant_id' => $advisor->id,
                'status'  => 'completed',
                'start_date'            => Carbon::parse('2025-12-01T00:00:00Z'),
                'next_milestone_label'  => null,
                'next_milestone_due_at' => null,
                'satisfaction'          => 5,
                'source_lead_id'        => null,
                'created_at'            => Carbon::parse('2025-12-01T08:00:00Z'),
                'updated_at'            => Carbon::parse('2026-04-30T17:00:00Z'),
            ],
            [
                'uuid'    => '01a4b2cf-0006-4ad0-9f2f-0a1b2c3d4e06',
                'name'    => 'Efua Asare',
                'email'   => 'efua@example.com',
                'phone'   => null,
                'program' => 'study-abroad',
                'consultant_id' => null,
                'status'  => 'onboarding',
                'start_date'            => Carbon::parse('2026-05-29T00:00:00Z'),
                'next_milestone_label'  => 'Assign consultant',
                'next_milestone_due_at' => Carbon::parse('2026-05-31T09:00:00Z'),
                'satisfaction'          => null,
                'source_lead_id'        => null,
                'created_at'            => Carbon::parse('2026-05-29T08:15:00Z'),
                'updated_at'            => Carbon::parse('2026-05-29T08:15:00Z'),
            ],
        ];
        foreach ($clients as $c) {
            \App\Models\Client::create($c);
        }
    }
}
