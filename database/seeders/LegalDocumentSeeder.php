<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. PRIVACY POLICY
        $privacyPolicyContent = <<<'HTML'
<h2 id="intro">1. Introduction &amp; Overview</h2>
<p>CloudKazi Elimu ("we," "our," "us," or "the Platform") is a multi-tenant school management information system and Software-as-a-Service (SaaS) platform built specifically to support Kenyan primary, junior secondary, and senior secondary schools, as well as educational institutions regionally. We are deeply committed to protecting the privacy, confidentiality, and security of all personal and institutional data entrusted to us, in strict compliance with the <strong>Kenya Data Protection Act No. 24 of 2019</strong>, the Data Protection (General) Regulations 2021, and international best practices for educational data privacy.</p>
<p>This Privacy Policy outlines how CloudKazi Elimu collects, processes, stores, discloses, and protects information when administrators, teachers, parents, guardians, students, and institutional staff interact with our web applications, portals, and integrated communication services.</p>

<h2 id="roles">2. Roles: Data Controller vs. Data Processor</h2>
<p>Under Kenyan Data Protection law:</p>
<ul>
    <li><strong>The School (Educational Institution)</strong> acts as the <strong>Data Controller</strong>. The School determines the purposes and means of collecting and processing personal data belonging to students, parents, guardians, and staff members.</li>
    <li><strong>CloudKazi Elimu</strong> acts as the <strong>Data Processor</strong>. We host, store, and process data strictly upon the instructions and authorization of the subscribing School, operating within secure multi-tenant infrastructure.</li>
</ul>

<h2 id="data-collection">3. Information We Collect</h2>
<p>We collect and manage several categories of information required to operate a full-fledged educational ERP:</p>

<h3 id="student-info">A. Student Information</h3>
<ul>
    <li>Full legal name, date of birth, gender, and photograph.</li>
    <li>Admission number, National Education Management Information System (NEMIS) / Unique Personal Identifier (UPI) numbers.</li>
    <li>Assigned class, stream, academic curriculum (CBC or 8-4-4 pathway).</li>
    <li>Hostel, dormitory, room, and transport route allocations.</li>
    <li>Special dietary or medical alerts provided by parents/guardians for emergency care.</li>
</ul>

<h3 id="parent-info">B. Parent &amp; Guardian Information</h3>
<ul>
    <li>Full name, relationship to student, National Identification / Passport number.</li>
    <li>Primary and alternative mobile telephone numbers (used for M-Pesa STK push and SMS alerts).</li>
    <li>Email address, postal address, and physical residential locality.</li>
    <li>Parent portal login credentials and authentication timestamps.</li>
</ul>

<h3 id="teacher-info">C. Teacher &amp; Staff Information</h3>
<ul>
    <li>Full name, TSC (Teachers Service Commission) number, national ID, email address, and mobile telephone.</li>
    <li>Departmental allocations, subject specialties, stream assignments, and timetable schedules.</li>
    <li>Staff biometric / attendance logs, leave records, and basic payroll/remuneration identifiers.</li>
</ul>

<h3>D. Academic & Examination Records</h3>
<ul>
    <li>Competency-Based Curriculum (CBC) formative and summative rubric assessments (Exceeding Expectation, Meeting Expectation, Approaching Expectation, Below Expectation).</li>
    <li>8-4-4 numerical examination marks, subject marks, class ranks, mean scores, and teacher comments.</li>
    <li>Historical report cards, certificates, and academic progression transcripts.</li>
</ul>

<h3 id="payment-info">E. Payment &amp; Financial Records</h3>
<ul>
    <li>Student fee invoice allocations, term fee balances, waiver records, and optional fee line items.</li>
    <li>Mobile money transaction data: M-Pesa transaction reference codes, sender phone numbers, amounts paid, payment timestamps, and Daraja API callback payloads.</li>
    <li>Credit/debit card tokens processed via certified PCI-DSS compliant third-party gateways (e.g., Paystack). CloudKazi Elimu never stores raw credit card CVVs or card numbers.</li>
    <li>School bank account reconciliation records and bursar manual receipt logs.</li>
</ul>

<h3>F. Cookies & Technical Telemetry</h3>
<ul>
    <li>Session identifiers, authentication tokens, and CSRF protection tokens necessary for state maintenance and cryptographic verification.</li>
    <li>Device browser type, operating system, IP address, and platform usage analytics used exclusively to prevent unauthorized account access and optimize platform performance.</li>
</ul>

<h2 id="data-usage">4. How We Use Your Information</h2>
<p>The information collected is utilized exclusively for lawful educational, administrative, and communication purposes:</p>
<ul>
    <li>Administering student admissions, enrollment, class allocation, and stream management.</li>
    <li>Calculating student attendance, generating CBC rubric reports, and rendering 8-4-4 termly report cards.</li>
    <li>Automating fee billing, issuing digital receipts, and verifying M-Pesa payment confirmations.</li>
    <li>Transmitting SMS and email notifications regarding academic results, fee balances, and school announcements.</li>
    <li>Powering the Elimu AI Help Assistant to guide users on system navigation and feature workflows.</li>
    <li>Maintaining comprehensive audit logs to ensure institutional compliance and accountability.</li>
</ul>

<h2 id="retention">5. Data Retention &amp; Archival Policies</h2>
<p>We maintain data in active storage for as long as the subscribing School holds an active subscription with CloudKazi Elimu. Upon termination or expiration of an institutional contract:</p>
<ul>
    <li>Schools are granted an export window (up to 90 days) to retrieve all student, academic, and financial records in standardized formats (CSV, Excel, PDF).</li>
    <li>Following the export window, active database instances are archived or securely anonymized according to institutional instruction and statutory retention laws governing Kenyan school archives.</li>
    <li>Automated disaster recovery backups expire on rolling cycles not exceeding 180 days.</li>
</ul>

<h2 id="security">6. Security Measures &amp; Infrastructure</h2>
<p>We deploy defense-in-depth security mechanisms designed to protect sensitive student and financial records:</p>
<ul>
    <li><strong>Multi-Tenant Isolation:</strong> Logical separation of school databases and tenant-scoped Eloquent models ensure that no school can access or view another institution's data.</li>
    <li><strong>Encryption:</strong> All data in transit is encrypted using Transport Layer Security (TLS 1.3). Sensitive credentials and tokens are hashed using bcrypt / Argon2 or encrypted at rest using AES-256.</li>
    <li><strong>Role-Based Access Control (RBAC):</strong> Strict separation of privileges across Super Admin, School Admin, Principals, Bursars, Teachers, Parents, Students, and Librarians.</li>
    <li><strong>Automated Backups:</strong> Daily automated database snapshots with encrypted off-site replication to ensure business continuity and disaster recovery.</li>
    <li><strong>Vulnerability Monitoring:</strong> Continuous dependency scanning, SQL injection prevention via parameterized PDO queries, and strict CSRF and XSS protection headers.</li>
</ul>

<h2 id="integrations">7. Third-Party Integrations</h2>
<p>To deliver core operational features, CloudKazi Elimu interfaces with trusted, certified third-party service providers under stringent data processing agreements:</p>
<ul>
    <li><strong>Safaricom Daraja API:</strong> Facilitates instant C2B and B2B M-Pesa school fee processing.</li>
    <li><strong>Paystack / Card Gateways:</strong> Provides PCI-DSS compliant online card and bank transfer checkout.</li>
    <li><strong>Telecom SMS Gateways:</strong> Transmits real-time transactional SMS alerts, fee receipts, and parent notifications.</li>
    <li><strong>Cloud Infrastructure Providers:</strong> ISO/IEC 27001 and SOC 2 certified data centers hosting our application containers, databases, and backup storage.</li>
</ul>

<h2 id="user-rights">8. User Rights &amp; Data Subject Access</h2>
<p>In accordance with Section 26 of the Kenya Data Protection Act, data subjects (or parents/guardians acting on behalf of minor students) maintain the following rights:</p>
<ul>
    <li><strong>Right of Access:</strong> The right to request a summary of personal information held by the school within the platform.</li>
    <li><strong>Right to Rectification:</strong> The right to have inaccurate, outdated, or incomplete academic or biographical records corrected by school administrators.</li>
    <li><strong>Right to Erasure / De-registration:</strong> Subject to mandatory statutory educational record retention laws, requests for record deletion may be coordinated through the school management.</li>
    <li><strong>Right to Object:</strong> The right to object to automated data profiling or non-essential communication campaigns.</li>
</ul>

<h2>9. Children’s Privacy</h2>
<p>Protecting the privacy of young learners is paramount. Student records in CloudKazi Elimu are entered solely by authorized school administrators and teachers pursuant to legitimate educational mandates. We never sell, monetize, or display behavioral advertisements to students or use student records for commercial marketing.</p>

<h2 id="contact">10. Contact Information &amp; Data Protection Officer</h2>
<p>For questions, data subject requests, or privacy concerns regarding CloudKazi Elimu, please contact our designated Data Protection Officer (DPO):</p>
<p>
    <strong>CloudKazi Data Protection Office</strong><br>
    Nairobi, Kenya<br>
    Email: <a href="mailto:privacy@cloudkazi.co.ke">privacy@cloudkazi.co.ke</a><br>
    Legal Inquiries: <a href="mailto:legal@cloudkazi.co.ke">legal@cloudkazi.co.ke</a><br>
    Support Telephone: +254 700 000 000
</p>
HTML;

        LegalDocument::updateOrCreate(
            ['type' => LegalDocument::TYPE_PRIVACY_POLICY],
            [
                'title'     => 'CloudKazi Elimu Privacy Policy',
                'content'   => $privacyPolicyContent,
                'version'   => '2.1',
                'is_active' => true,
            ]
        );

        // 2. TERMS AND CONDITIONS
        $termsContent = <<<'HTML'
<h2 id="acceptance">1. Acceptance of Terms &amp; Service Scope</h2>
<p>These Terms and Conditions ("Terms," "Agreement") constitute a legally binding agreement between CloudKazi Technologies Ltd ("CloudKazi," "we," "us," or "Service Provider") and the educational institution, school management board, administrator, or individual user ("School," "Subscriber," "User," or "You") accessing the <strong>CloudKazi Elimu</strong> school management information system and related applications.</p>
<p>By creating an institutional account, subscribing to a software package, logging into any portal, or utilizing our services, you expressly agree to be bound by these Terms. If you are accepting on behalf of a school or educational institution, you represent and warrant that you possess full legal authority to bind that entity to this Agreement.</p>

<h2 id="user-responsibilities">2. User Responsibilities &amp; Acceptable Use</h2>
<p>All users authorized by an institution agree to:</p>
<ul>
    <li>Provide accurate, truthful, and up-to-date institutional, staff, guardian, and student enrollment information.</li>
    <li>Keep account credentials, passwords, and two-factor authentication tokens strictly confidential.</li>
    <li>Use the system solely for legitimate educational administration, curriculum assessment, financial record keeping, and school communication.</li>
    <li>Promptly report any suspected security breach, unauthorized credential compromise, or system irregularity to the School Administrator and CloudKazi Support.</li>
</ul>

<h2 id="school-responsibilities">3. School &amp; Institutional Responsibilities</h2>
<p>The subscribing School is responsible for:</p>
<ul>
    <li>Securing all necessary consents from parents, legal guardians, and staff members prior to uploading personal data into the system, in conformity with the Kenya Data Protection Act 2019.</li>
    <li>Designating authorized personnel with appropriate administrative privileges (e.g., Bursar, Principal, Class Teacher) and ensuring departing staff credentials are deactivated immediately.</li>
    <li>Verifying the accuracy of fee structures, curriculum grading scales, and report card comments prior to publishing to parents or education ministry bodies.</li>
    <li>Maintaining adequate internet connectivity and compatible end-user devices to access the SaaS platform.</li>
</ul>

<h2 id="data-ownership">4. Data Ownership &amp; Intellectual Property</h2>
<span id="ip"></span>
<ul>
    <li><strong>School Data:</strong> The School retains 100% full legal ownership, title, and intellectual property rights over all institutional data, student rosters, academic records, fee transactions, and communication logs uploaded to or generated within CloudKazi Elimu.</li>
    <li><strong>Platform Intellectual Property:</strong> CloudKazi retains all exclusive rights, title, patent, copyright, trademark, and trade secrets in the CloudKazi Elimu software, source code, user interface designs, AI algorithmic architectures, database schemas, and documentation. No rights are granted except the limited, revocable, non-exclusive license to use the service during an active subscription.</li>
</ul>

<h2 id="subscription">5. Subscription Usage, Tiers &amp; Student Quotas</h2>
<ul>
    <li>Services are provisioned based on the subscribed package tier (e.g., Basic, Standard, Premium, Enterprise) and the student population quota allocated to the School.</li>
    <li>Attempting to register students beyond the allocated student limit requires an administrative plan upgrade or quota expansion through the Super Admin platform.</li>
    <li>CloudKazi reserves the right to enforce feature constraints (such as SMS limits, automated timetable generation runs, or AI help assistant usage) corresponding to your active plan.</li>
</ul>

<h2 id="account-security">6. Account Security &amp; Credential Protection</h2>
<ul>
    <li>Sharing individual user accounts among multiple staff members is strictly prohibited. Every teacher, administrator, and bursar must have an individual named user profile.</li>
    <li>You are solely responsible for all activities and transactions performed under your authenticated user session.</li>
    <li>CloudKazi implements automated audit logging tracking critical actions (fee revisions, grade alterations, student promotions, and user role modifications) for forensic verification.</li>
</ul>

<h2 id="prohibited">7. Prohibited Activities</h2>
<p>Users and institutions shall not:</p>
<ul>
    <li>Reverse engineer, decompile, disassemble, or attempt to derive the underlying source code of CloudKazi Elimu.</li>
    <li>Probe, scan, or test the vulnerability of the system, networks, or API endpoints without prior express written authorization from CloudKazi Security.</li>
    <li>Upload viruses, worms, trojans, or malicious payloads through file attachment or learning material modules.</li>
    <li>Use automated scrapers, crawlers, or bots to harvest data from portals or public endpoints.</li>
    <li>Send unsolicited bulk communications, abusive messages, or illegal content via integrated SMS or email dispatchers.</li>
</ul>

<h2 id="payments">8. Payment Obligations &amp; Billing</h2>
<ul>
    <li>Subscription fees are invoiced according to the selected billing cycle (Termly or Annually) and must be settled via authorized channels: Safaricom M-Pesa Paybill/Till, Bank Transfer, or supported debit/credit card.</li>
    <li>Invoices are due within the stated credit terms. Failure to remit subscription payments within the grace period (typically 14 calendar days) may result in restricted institutional access or temporary service suspension.</li>
    <li>All quoted fees are exclusive of applicable statutory taxes (such as VAT) unless explicitly stated otherwise.</li>
</ul>

<h2 id="sla">9. System Availability &amp; Service Level Agreement (SLA)</h2>
<ul>
    <li>We endeavor to maintain a platform service uptime of <strong>99.5%</strong> during school operating hours, excluding scheduled maintenance windows.</li>
    <li>Routine maintenance and upgrades are typically scheduled during off-peak hours (weekends or overnight EAT), with advance administrative notification dispatched via email or platform banners.</li>
    <li>CloudKazi is not liable for service degradation caused by third-party telecom outages, local internet service interruptions, or Safaricom Daraja maintenance downtime.</li>
</ul>

<h2 id="termination">10. Service Termination &amp; Account Suspension</h2>
<ul>
    <li><strong>Termination for Convenience:</strong> An institution may terminate its subscription at the conclusion of a billing cycle by providing thirty (30) days prior written notice.</li>
    <li><strong>Suspension for Cause:</strong> CloudKazi may suspend or terminate account access immediately if the subscriber breaches these Terms, engages in fraudulent financial activities, or fails to settle overdue invoices past the grace period.</li>
    <li><strong>Off-boarding Data Export:</strong> Upon formal termination, CloudKazi will facilitate a complete export of the School's academic and financial data within thirty (30) days, after which data is archived and eventually purged in accordance with our retention policy.</li>
</ul>

<h2 id="liability">11. Limitation of Liability</h2>
<p>To the maximum extent permitted under Kenyan law, CloudKazi Technologies Ltd, its directors, employees, and affiliates shall not be liable for any indirect, incidental, consequential, special, or punitive damages, including loss of profits, data corruption, or educational operational delays arising from the use or inability to use the platform. Our total cumulative liability under this Agreement shall not exceed the subscription fees paid by the School during the preceding three (3) months.</p>

<h2 id="disputes">12. Dispute Resolution &amp; Governing Law</h2>
<p>This Agreement shall be governed by, interpreted, and construed in accordance with the <strong>Laws of the Republic of Kenya</strong>. Any dispute, controversy, or claim arising out of or relating to this contract shall first be submitted to good-faith mutual negotiation between the designated representatives of both parties. If unresolved within thirty (30) days, the dispute shall be referred to final and binding arbitration in Nairobi, Kenya, in accordance with the Arbitration Act 1995 (as amended).</p>

<h2>13. Amendments to Terms</h2>
<p>CloudKazi reserves the right to update or modify these Terms periodically. Subscribing schools will be notified of material changes via email or an in-app administrative banner at least fifteen (15) days prior to the effective date. Continued use of the platform after updates take effect constitutes full acceptance of the revised Terms.</p>

<h2>14. Contact & Legal Notices</h2>
<p>Official legal notices or inquiries regarding these Terms should be directed to:</p>
<p>
    <strong>CloudKazi Technologies Legal Department</strong><br>
    Nairobi, Kenya<br>
    Email: <a href="mailto:legal@cloudkazi.co.ke">legal@cloudkazi.co.ke</a><br>
    Customer Care: <a href="mailto:support@cloudkazi.co.ke">support@cloudkazi.co.ke</a><br>
    Direct Line: +254 700 000 000
</p>
HTML;

        LegalDocument::updateOrCreate(
            ['type' => LegalDocument::TYPE_TERMS_CONDITIONS],
            [
                'title'     => 'CloudKazi Elimu Terms and Conditions',
                'content'   => $termsContent,
                'version'   => '2.1',
                'is_active' => true,
            ]
        );

        $assistantDefaults = [
            'chatbot_enabled'         => '1',
            'chatbot_welcome_message' => 'Hello! I am the Elimu AI Assistant. Ask me anything about using CloudKazi Elimu (e.g. registering students, fees, or report cards).',
            'chatbot_prompt_1'        => 'How do I register a student?',
            'chatbot_prompt_2'        => 'How do I record fees?',
            'chatbot_prompt_3'        => 'How do I generate report cards?',
            'chatbot_prompt_4'        => 'How do I generate automatic timetables?',
            'openai_enabled'          => '0',
            'openai_model'            => 'gpt-4o-mini',
        ];

        foreach ($assistantDefaults as $key => $value) {
            \App\Models\PlatformSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'ai_assistant']
            );
        }
    }
}
