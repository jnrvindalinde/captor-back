<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Resource;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Seeds blog posts, resources (guides/documents/videos/audio/external) and
 * success stories with rich, realistic copy and free-license media URLs
 * (Unsplash for imagery, Pixabay for audio).
 *
 * Run with:
 *   php artisan db:seed --class=ContentDemoSeeder
 *
 * Safe to re-run: uses updateOrCreate keyed on slug.
 */
class ContentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@local.com'],
            ['name' => 'Local Admin', 'password' => bcrypt('password'), 'role' => 'super_admin'],
        );

        $this->seedPosts($admin->id);
        $this->seedResources($admin->id);
        $this->seedStories($admin->id);
    }

    // ---------------------------------------------------------------- POSTS

    private function seedPosts(int $authorId): void
    {
        $posts = [
            [
                'slug'  => 'how-to-write-a-statement-of-purpose-that-sounds-like-you',
                'title' => 'How to write a statement of purpose that actually sounds like you',
                'excerpt' => 'Admissions committees read hundreds of SOPs that all start the same way. Here is how to write one that doesn\'t.',
                'cover' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=1600&auto=format&fit=crop&q=80',
                'tags'  => ['Admissions', 'Writing', 'SOP'],
                'published' => '2026-04-12T10:00:00Z',
                'body'  => '<p>Most statements of purpose fail the same way: they open with a sweeping line about a childhood fascination, glide through a tour of accomplishments, and close with a promise to "contribute to the global community." The committee reading you has seen that essay a thousand times.</p>'
                    .'<p>The fix is not literary. It is structural. A strong SOP answers four questions in order, and it answers them with evidence — not adjectives.</p>'
                    .'<h2>1. What specific problem do you want to work on?</h2>'
                    .'<p>Not a field. A problem. "Public health" is a field. "Why does anaemia screening drop off the second a child turns five in peri-urban Accra?" is a problem. The narrower the question, the easier it is for a reader to picture you in their lab, their seminar, their cohort.</p>'
                    .'<h2>2. How did you arrive at it?</h2>'
                    .'<p>Walk through the actual moment — the conversation, the dataset, the failed pilot — that made the question real to you. One paragraph. No metaphors.</p>'
                    .'<h2>3. What have you already done about it?</h2>'
                    .'<p>This is the section most candidates skip. List the specific work you have done that proves you are not bluffing: the side project, the volunteer rotation, the paper you co-authored, the dataset you scraped.</p>'
                    .'<h2>4. Why this program, this faculty, this year?</h2>'
                    .'<p>Name two or three faculty members and one specific reason their work matters to your question. If you cannot do that, you are applying to the wrong school.</p>'
                    .'<blockquote>If you remove "passion," "global," and "community" from your draft and it falls apart, you have not written an SOP yet. You have written a brochure.</blockquote>'
                    .'<p>Write the four sections in order. Cut everything that does not advance one of them. Then read it aloud — if it sounds like a press release, start again.</p>',
            ],
            [
                'slug'  => 'the-shortlist-conversation-most-applicants-never-have',
                'title' => 'The shortlist conversation most applicants never have',
                'excerpt' => 'A good shortlist is not about reach, target and safety. It is about what you would actually accept.',
                'cover' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1600&auto=format&fit=crop&q=80',
                'tags'  => ['Admissions', 'Strategy'],
                'published' => '2026-03-30T08:30:00Z',
                'body'  => '<p>Every applicant we meet arrives with a list of fifteen programs scraped from a ranking site. Almost none of them have asked themselves the only question that matters: which of these would I actually say yes to?</p>'
                    .'<p>Build the shortlist backwards. Start with two non-negotiables — they can be funding, location, methodology, language of instruction, faculty access, whatever — and remove everything that fails them. You will be left with a much shorter list, and every application you write will be sharper because you actually want to be there.</p>'
                    .'<h3>What we ask in the first session</h3>'
                    .'<ul><li>If you were offered admission to your top "reach" tomorrow but without funding, would you go?</li>'
                    .'<li>What is the one thing you need to be able to do in year one that you cannot do now?</li>'
                    .'<li>What city or environment will let you do your best work, and which will quietly drain you?</li></ul>'
                    .'<p>Those answers usually cut a list of fifteen down to six. The remaining six get a full strategy, a full application, and a real shot.</p>',
            ],
            [
                'slug'  => 'preparing-for-the-interview-without-rehearsing-to-death',
                'title' => 'Preparing for the interview without rehearsing yourself to death',
                'excerpt' => 'Memorised answers fail the moment a question shifts. Here is the prep that holds up under pressure.',
                'cover' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1600&auto=format&fit=crop&q=80',
                'tags'  => ['Interviews', 'Career'],
                'published' => '2026-03-14T12:00:00Z',
                'body'  => '<p>Interview prep that consists of memorising answers to fifty questions fails on the fifty-first. The interviewer always finds it — and you freeze.</p>'
                    .'<p>Prepare three things instead.</p>'
                    .'<h2>1. Your through-line</h2>'
                    .'<p>One sentence that connects what you have done, what you want to do, and why this seat is the right one. If you cannot say it in fifteen seconds, write it again.</p>'
                    .'<h2>2. Five proof stories</h2>'
                    .'<p>Five concrete situations from your past — not bullet points, full stories with stakes and outcome — that you can pull from to answer almost any behavioural prompt. Practise telling them in 90 seconds.</p>'
                    .'<h2>3. Three questions you actually want answered</h2>'
                    .'<p>Not "what is the culture like." Real questions about the team, the program, or the role that you genuinely cannot Google. The interviewer will remember you for these.</p>'
                    .'<p>That is the entire system. Do it for two evenings instead of two weeks and you will walk in lighter, not heavier.</p>',
            ],
            [
                'slug'  => 'funding-your-masters-without-burning-out-on-applications',
                'title' => 'Funding your masters without burning out on scholarship applications',
                'excerpt' => 'Most scholarships reward fit and specificity over volume. A focused list of five beats a frantic list of thirty.',
                'cover' => 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=1600&auto=format&fit=crop&q=80',
                'tags'  => ['Scholarships', 'Funding'],
                'published' => '2026-02-22T09:00:00Z',
                'body'  => '<p>The candidates who fund their degrees fully are almost never the ones who applied to the most scholarships. They are the ones who picked five they fit and wrote each application as if it were the only one.</p>'
                    .'<p>Use this filter before you start any application:</p>'
                    .'<ol><li>Do I meet 100% of the eligibility criteria — including the small print on nationality, age, field and prior degree?</li>'
                    .'<li>Can I name three reasons my profile matches the funder\'s stated mission, not just the field?</li>'
                    .'<li>Do I have a referee who can speak to the specific thing this scholarship cares about?</li></ol>'
                    .'<p>If you cannot answer yes to all three, the application is not worth the weekend. Spend it on one where you can.</p>',
            ],
            [
                'slug'  => 'visas-housing-banking-the-quiet-second-half-of-getting-in',
                'title' => 'Visas, housing, banking: the quiet second half of getting in',
                'excerpt' => 'The offer letter is the easy part. The next twelve weeks decide whether you actually arrive.',
                'cover' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1600&auto=format&fit=crop&q=80',
                'tags'  => ['Visas', 'Pre-departure'],
                'published' => '2026-02-05T11:30:00Z',
                'body'  => '<p>We watch candidates celebrate the offer letter and then disappear into the bureaucratic fog of visa appointments, accommodation deposits and tuition wires. Some never come out.</p>'
                    .'<p>Build the second-half plan the week the offer arrives, not the week before departure.</p>'
                    .'<h3>The checklist that matters</h3>'
                    .'<ul><li><strong>Visa appointment</strong> booked within seven days — slots vanish quickly in May and August.</li>'
                    .'<li><strong>Proof of funds</strong> letter from your bank, with the exact wording the consulate requires.</li>'
                    .'<li><strong>Accommodation</strong> shortlist with three options ranked by commute, not aesthetics.</li>'
                    .'<li><strong>International transfer</strong> route tested with a small amount before the tuition wire.</li>'
                    .'<li><strong>One person on the ground</strong> who can receive a package or vouch for you on arrival.</li></ul>'
                    .'<p>None of this is glamorous. All of it is what separates the offers that turn into degrees from the ones that quietly expire.</p>',
            ],
            [
                'slug'  => 'when-to-defer-when-to-decline-when-to-take-the-offer',
                'title' => 'When to defer, when to decline, when to take the offer',
                'excerpt' => 'An offer is a question, not an answer. Here is how we help candidates decide.',
                'cover' => 'https://images.unsplash.com/photo-1507537297725-24a1c029d3ca?w=1600&auto=format&fit=crop&q=80',
                'tags'  => ['Decisions', 'Strategy'],
                'published' => '2026-01-18T08:00:00Z',
                'body'  => '<p>Acceptance season is loud. Friends celebrate, parents call, the school sends a glossy welcome pack. In the middle of the noise, the actual decision often gets skipped.</p>'
                    .'<p>We work through three questions with every candidate sitting on an offer.</p>'
                    .'<h2>Is this the version of the offer you wanted?</h2>'
                    .'<p>Half-funded at your top school is not the same opportunity as fully funded at your second. Write both scenarios out in plain language before you respond to either.</p>'
                    .'<h2>What would defer mean in practice?</h2>'
                    .'<p>A deferral is only useful if the year ahead has a defined shape — a project, a role, a research collaboration. Otherwise it becomes a year of waiting that erodes your motivation.</p>'
                    .'<h2>What would you regret most in five years?</h2>'
                    .'<p>Not the offer you turned down. The version of yourself you did not invest in. That is usually the answer.</p>',
            ],
        ];

        foreach ($posts as $p) {
            Post::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title'        => $p['title'],
                    'excerpt'      => $p['excerpt'],
                    'body'         => $p['body'],
                    'cover_image'  => $p['cover'],
                    'status'       => Post::STATUS_PUBLISHED,
                    'tags'         => $p['tags'],
                    'author_id'    => $authorId,
                    'published_at' => Carbon::parse($p['published']),
                ],
            );
        }
    }

    // ------------------------------------------------------------ RESOURCES

    private function seedResources(int $authorId): void
    {
        // Pixabay-hosted MP3 (Pixabay Content License — free for commercial use,
        // no attribution required). These are stable CDN URLs.
        $audioCalm = 'https://cdn.pixabay.com/audio/2022/03/15/audio_1d29ea1d8c.mp3';
        $audioFocus = 'https://cdn.pixabay.com/audio/2022/10/30/audio_347111d654.mp3';

        // Sample / placeholder PDFs (W3C and Mozilla host these as public test fixtures).
        $samplePdf = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
        $traceMonkey = 'https://mozilla.github.io/pdf.js/web/compressed.tracemonkey-pldi-09.pdf';

        $resources = [
            [
                'slug' => 'sop-workbook-2026',
                'title' => 'Statement of Purpose Workbook (2026 edition)',
                'description' => 'A 28-page workbook that walks you through the four-part SOP structure with prompts, examples and the exact red flags admissions committees flag in the first paragraph.',
                'format' => 'guide',
                'file_path' => $traceMonkey,
                'file_label' => 'SOP Workbook 2026.pdf',
                'cover' => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['SOP', 'Workbook', 'Admissions'],
            ],
            [
                'slug' => 'scholarship-shortlist-template',
                'title' => 'Scholarship shortlist template (Notion / spreadsheet)',
                'description' => 'The exact tracker our advisors build with every funded applicant. Eligibility filters, deadline rollups, document requirements and a fit score per scholarship.',
                'format' => 'document',
                'file_path' => $samplePdf,
                'file_label' => 'Scholarship Shortlist Template.pdf',
                'cover' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Scholarships', 'Template'],
            ],
            [
                'slug' => 'cv-rebuild-checklist',
                'title' => 'The 14-point CV rebuild checklist',
                'description' => 'A practical pass on your CV — line by line — used by our career-pivot clients. Includes the three sections most candidates over-write and the one most leave out.',
                'format' => 'guide',
                'file_path' => $samplePdf,
                'file_label' => 'CV Rebuild Checklist.pdf',
                'cover' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['CV', 'Career'],
            ],
            [
                'slug' => 'interview-prep-audio-walkthrough',
                'title' => 'Interview prep — a 12-minute audio walkthrough',
                'description' => 'A short audio session you can listen to the night before a graduate or scholarship interview. Covers the through-line, the proof-story method and one breathing exercise that actually works.',
                'format' => 'audio',
                'file_path' => $audioCalm,
                'file_label' => 'Interview Prep Walkthrough.mp3',
                'cover' => 'https://images.unsplash.com/photo-1478737270239-2f02b77fc618?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Interviews', 'Audio'],
            ],
            [
                'slug' => 'study-abroad-focus-soundscape',
                'title' => 'Focus soundscape for application weekends',
                'description' => 'A 30-minute instrumental loop several of our clients have used during their final draft passes. Headphones recommended.',
                'format' => 'audio',
                'file_path' => $audioFocus,
                'file_label' => 'Focus Soundscape.mp3',
                'cover' => 'https://images.unsplash.com/photo-1499415479124-43c32433a620?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Focus', 'Audio'],
            ],
            [
                'slug' => 'mock-interview-questions-master-list',
                'title' => 'Mock interview question bank (graduate + scholarship)',
                'description' => 'Over 120 real questions, sorted by program type and stakes — from MBA behavioural to Chevening scholarship panel. With notes on what each question is really probing for.',
                'format' => 'document',
                'file_path' => $traceMonkey,
                'file_label' => 'Mock Interview Question Bank.pdf',
                'cover' => 'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Interviews', 'Bank'],
            ],
            [
                'slug' => 'visa-document-bundle-uk-eu-us',
                'title' => 'Visa document bundle — UK, EU, US (2026)',
                'description' => 'The exact paperwork checklist, sample cover letters and proof-of-funds wording for the three most common destinations our clients apply to. Updated for the 2026 intake.',
                'format' => 'guide',
                'file_path' => $traceMonkey,
                'file_label' => 'Visa Document Bundle 2026.pdf',
                'cover' => 'https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Visa', 'Pre-departure'],
            ],
            [
                'slug' => 'mastercard-foundation-scholarship-portal',
                'title' => 'Mastercard Foundation Scholars Program — portal',
                'description' => 'The single most-asked-about scholarship from our applicants. Direct link to the official portal with eligibility, deadlines and partner institutions.',
                'format' => 'external',
                'external_url' => 'https://mastercardfdn.org/all/scholars/',
                'cover' => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Scholarships', 'External'],
            ],
            [
                'slug' => 'chevening-uk-government-scholarship',
                'title' => 'Chevening — UK Government Scholarships',
                'description' => 'Fully-funded UK masters scholarships open to professionals from across the world, including all African nations. Annual cycle opens in August.',
                'format' => 'external',
                'external_url' => 'https://www.chevening.org/',
                'cover' => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Scholarships', 'UK'],
            ],
            [
                'slug' => 'daad-germany-funded-study',
                'title' => 'DAAD — funded study in Germany',
                'description' => 'The German Academic Exchange Service portal: scholarships, low-cost public universities, English-taught masters and step-by-step application guidance.',
                'format' => 'external',
                'external_url' => 'https://www.daad.de/en/',
                'cover' => 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=1600&auto=format&fit=crop&q=80',
                'tags' => ['Scholarships', 'Germany'],
            ],
        ];

        foreach ($resources as $r) {
            Resource::updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'title'        => $r['title'],
                    'description'  => $r['description'],
                    'format'       => $r['format'],
                    'file_path'    => $r['file_path'] ?? null,
                    'file_label'   => $r['file_label'] ?? null,
                    'external_url' => $r['external_url'] ?? null,
                    'cover_image'  => $r['cover'],
                    'status'       => Resource::STATUS_PUBLISHED,
                    'tags'         => $r['tags'],
                    'author_id'    => $authorId,
                ],
            );
        }
    }

    // -------------------------------------------------------------- STORIES

    private function seedStories(int $authorId): void
    {
        $stories = [
            [
                'slug' => 'akosua-b-career-pivot',
                'title' => 'From corporate marketing to leading curriculum design',
                'person_name' => 'Akosua B.',
                'person_role' => 'Mid-career professional · Accra',
                'outcome' => 'transition',
                'outcome_label' => 'Career repositioned',
                'categories' => ['Career'],
                'quote' => 'I came in unsure between two career paths and walked out with a one-year plan that finally felt like mine. The advisory was practical, kind, and never generic.',
                'cover' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1497486751825-1233686d5d80?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'A six-year marketing career rerouted into impact-driven education work in twelve focused months.',
                'body' => '<p>Akosua had spent six years in corporate marketing, but the role no longer aligned with her values. She knew she wanted a change, but was torn between two paths: scaling up into a director role within a tech startup, or pivoting entirely into education and impact-driven work.</p>'
                    .'<p>In our first session, we mapped out her skills, her motivations, and the real constraints she was working within. We didn\'t try to convince her one way or the other. Instead, we walked through what each path would actually look like — salary, hours, daily work, growth trajectory, and alignment with her stated values.</p>'
                    .'<p>By the end, Akosua had clarity. She took a year off, completed a short teacher training certification, and now leads curriculum design for a tech education nonprofit in Accra. She tells us the one-year plan we built has stayed accurate every step of the way.</p>',
            ],
            [
                'slug' => 'kwadwo-m-mastercard-edinburgh',
                'title' => 'A specific SOP, fully-funded by Mastercard',
                'person_name' => 'Kwadwo M.',
                'person_role' => 'MSc Public Health · Edinburgh, 2025',
                'outcome' => 'scholarship',
                'outcome_label' => 'Fully funded — Mastercard',
                'categories' => ['School', 'Scholarship'],
                'quote' => 'They helped me shape an SOP that actually sounded like me, then walked me through every step of my Mastercard Foundation application until the offer came in.',
                'cover' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1532619675605-1ede6c2ed2b0?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'A generic SOP rewritten around a single, specific public health question — and a fully-funded Mastercard offer to Edinburgh.',
                'body' => '<p>Kwadwo is a public health professional from Accra interested in epidemiology and global health systems. He had a strong application profile — good undergraduate grades, two years of fieldwork experience — but his statement of purpose read like a generic mission statement. It could have been written by anyone.</p>'
                    .'<p>We spent two sessions on the SOP. First, we interviewed him on his actual decisions: Why epidemiology? What moment changed his mind? What research question keeps him up at night? What did he learn that surprised him?</p>'
                    .'<p>His rewritten SOP was 40% shorter, three times more specific, and it centred on a single public health challenge he had observed in Ghana that got him into the field in the first place. That specificity got him into the Mastercard Foundation Scholars program with full funding to Edinburgh.</p>',
            ],
            [
                'slug' => 'nana-a-two-offers-six-weeks',
                'title' => 'Two senior engineering offers in six weeks',
                'person_name' => 'Nana A.',
                'person_role' => 'Software Engineer · Remote, EU',
                'outcome' => 'placement',
                'outcome_label' => 'Two offers in six weeks',
                'categories' => ['Job', 'Career'],
                'quote' => 'Career 360 reviewed my CV, prepped me for three interviews, and connected me with mentors in my field. I had two offers within six weeks.',
                'cover' => 'https://images.unsplash.com/photo-1573497019418-b400bb3ab074?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'CV repositioning, interview rehearsal and two warm intros — a remote backend lead role in Barcelona.',
                'body' => '<p>Nana had been working as a mid-level engineer in Lagos but wanted to explore opportunities in the EU tech market. She had strong technical skills but had never interviewed at a senior level for global companies.</p>'
                    .'<p>We worked through CV positioning, technical interview strategy, and salary negotiation. More importantly, we connected her with two engineers in our network who were already working remotely in Berlin and Amsterdam. They gave her real feedback on what EU tech companies look for and what the day-to-day actually looks like.</p>'
                    .'<p>Six weeks later, Nana had two offers — one from Berlin, one from a distributed startup based in Barcelona. She chose the Barcelona role and is now leading backend infrastructure for a Series B company.</p>',
            ],
            [
                'slug' => 'efua-o-sciences-po-funded',
                'title' => 'Three rejections, then Sciences Po with funding',
                'person_name' => 'Efua O.',
                'person_role' => 'MA International Relations · Sciences Po',
                'outcome' => 'admission',
                'outcome_label' => 'Admitted with funding',
                'categories' => ['School', 'Scholarship'],
                'quote' => 'I had three rejections before I came in. We rebuilt my application from scratch, reframed my story, and the next round produced two acceptances.',
                'cover' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'A generic IR essay replaced by a specific, argued case for one school — Sciences Po, with a partial scholarship.',
                'body' => '<p>Efua applied to five master\'s programs in international relations. She was rejected from three, waitlisted from one, and admitted to a safety school. She wanted to reapply the following year but felt stuck — she did not know what had gone wrong.</p>'
                    .'<p>We audited her applications and found the issue was not her CV or grades. It was her SOP. She had written a generic essay about global affairs instead of a specific, argued case for why she wanted to study IR at each specific school.</p>'
                    .'<p>We rebuilt her approach completely. For Sciences Po specifically, she wrote about her background in West African trade policy and why their program\'s methodology was the only one that would prepare her for her research agenda. We also reframed her background — she had downplayed her policy work, but that was actually her strongest asset.</p>'
                    .'<p>The revised application got her admitted to Sciences Po with a partial scholarship.</p>',
            ],
            [
                'slug' => 'jojo-k-mba-toronto',
                'title' => 'A clearer post-MBA hypothesis, two scholarship offers',
                'person_name' => 'Jojo K.',
                'person_role' => 'MBA candidate · Toronto',
                'outcome' => 'admission',
                'outcome_label' => 'Two MBA offers — Rotman & Schulich',
                'categories' => ['School', 'Career'],
                'quote' => 'The intake form felt rigorous before the call — and it was. By the time we met, my advisor already knew exactly what to challenge me on.',
                'cover' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'Climate-tech pivot articulated as a specific, testable hypothesis — Rotman and Schulich both said yes, both with scholarships.',
                'body' => '<p>Jojo worked in fintech in Singapore and wanted to pursue an MBA to transition into climate tech investing. He had never written a GMAT essay or thought through his post-MBA goal deeply.</p>'
                    .'<p>Our application form asked him specific questions: What will you do in year one post-MBA that you cannot do now? Which three companies will you definitely apply to for internships? When our team reviewed his answers, we saw he had not thought this through clearly yet.</p>'
                    .'<p>In our session, we challenged him on every vague statement. Not to be difficult, but because admissions committees will do the same — and he was competing against people with crystal-clear answers. By the end of the call, Jojo had a specific, testable hypothesis about what an MBA would enable him to do.</p>'
                    .'<p>He applied with that clarity. He got into Rotman and Schulich with scholarship offers from both.</p>',
            ],
            [
                'slug' => 'adwoa-y-tum-daad',
                'title' => 'A focused German shortlist, DAAD-funded at TU Munich',
                'person_name' => 'Adwoa Y.',
                'person_role' => 'BSc Computer Science · TU Munich',
                'outcome' => 'scholarship',
                'outcome_label' => 'Undergrad admission, DAAD funded',
                'categories' => ['School', 'Scholarship'],
                'quote' => 'What I valued most was the honesty. They told me which schools were a stretch and which were a fit. That clarity saved me a year.',
                'cover' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1496917756835-20cb06e75b4e?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1564981797816-1043664bf78d?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'Three German universities admitted her; the DAAD covered the cost. A year saved by an honest shortlist.',
                'body' => '<p>Adwoa is a high-performing secondary student in Accra interested in computer science and wanted to study in Germany (tuition-free or low-cost). Her parents wanted her to apply to US schools too, but she was concerned about cost and visa uncertainty.</p>'
                    .'<p>We mapped out a realistic shortlist for her: schools where she had a real shot based on her profile (TU Munich, TU Berlin, Technische Universität Darmstadt), not schools where she was gambling on an unpredictable admissions process.</p>'
                    .'<p>Adwoa applied to five German universities and got into three. More importantly, she was awarded a DAAD scholarship (German government funding for international students) at TU Munich. She starts this fall with costs covered.</p>'
                    .'<p>In retrospect, Adwoa says the shortlist clarity saved her a year of uncertainty. Instead of applying broadly and hoping, she had a focused plan.</p>',
            ],
            [
                'slug' => 'samuel-o-chevening-lse',
                'title' => 'Public sector to LSE — a Chevening story',
                'person_name' => 'Samuel O.',
                'person_role' => 'MPA Public Policy · LSE, 2024',
                'outcome' => 'scholarship',
                'outcome_label' => 'Chevening Scholar — fully funded',
                'categories' => ['School', 'Scholarship', 'Career'],
                'quote' => 'My first Chevening application was rejected. The second one, after we rebuilt the leadership essay, came back as a full scholarship.',
                'cover' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'A reframed leadership essay turned a rejected Chevening application into a fully funded LSE offer.',
                'body' => '<p>Samuel had worked four years in a regulatory agency and applied to Chevening the first time on his own. He was rejected at the longlist stage. He came to us for the second attempt.</p>'
                    .'<p>The first essay had been a tour of his career. The new one focused on a single reform he had quietly led inside his agency — small, but with measurable impact — and on the policy question he wanted to answer at LSE.</p>'
                    .'<p>The new application made the longlist, the shortlist, and the final cohort. He started his MPA at LSE the following September.</p>',
            ],
            [
                'slug' => 'naa-d-product-pivot-amsterdam',
                'title' => 'From consulting to product, in Amsterdam',
                'person_name' => 'Naa D.',
                'person_role' => 'Product Manager · Amsterdam',
                'outcome' => 'placement',
                'outcome_label' => 'Product offer in three months',
                'categories' => ['Job', 'Career'],
                'quote' => 'I had been a consultant for five years. They helped me translate that into a product manager story that hiring teams actually understood.',
                'cover' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=800&h=800&auto=format&fit=crop&q=80',
                'gallery' => [
                    'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=1200&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1551434678-e076c223a692?w=1200&auto=format&fit=crop&q=80',
                ],
                'summary' => 'Five years of consulting reframed into a credible PM portfolio — and a senior product role in three months.',
                'body' => '<p>Naa had been at a Big Four consultancy for five years and wanted to move into product management in Europe. Her CV read like a list of engagements; hiring teams could not picture her shipping software.</p>'
                    .'<p>We restructured her CV around outcomes, not engagements. We picked three projects where she had owned a product-like decision and rewrote them as case studies. We rehearsed product-sense interviews and prepared two take-home briefs.</p>'
                    .'<p>Three months later, she accepted a senior PM role at a scale-up in Amsterdam.</p>',
            ],
        ];

        foreach ($stories as $s) {
            Story::updateOrCreate(
                ['slug' => $s['slug']],
                [
                    'title'         => $s['title'],
                    'summary'       => $s['summary'],
                    'quote'         => $s['quote'],
                    'body'          => $s['body'],
                    'person_name'   => $s['person_name'],
                    'person_role'   => $s['person_role'],
                    'outcome'       => $s['outcome'],
                    'outcome_label' => $s['outcome_label'],
                    'categories'    => $s['categories'],
                    'cover_image'   => $s['cover'],
                    'gallery'       => $s['gallery'],
                    'status'        => Story::STATUS_PUBLISHED,
                    'author_id'     => $authorId,
                ],
            );
        }
    }
}
