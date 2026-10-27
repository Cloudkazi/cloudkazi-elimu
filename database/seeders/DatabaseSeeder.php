<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Asset;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookIssue;
use App\Models\CbcAssessment;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\DisciplineIncident;
use App\Models\Dormitory;
use App\Models\Employee;
use App\Models\Examination;
use App\Models\ExamMark844;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\HostelRoom;
use App\Models\LearningMaterial;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\SmsLog;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentHostel;
use App\Models\StudentInvoice;
use App\Models\StudentReportSummary;
use App\Models\StudentTransport;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherSubjectAllocation;
use App\Models\Timetable;
use App\Models\TransportRoute;
use App\Models\User;
use App\Services\AcademicAssessmentService;
use App\Services\TimetableEngineService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $rolesData = [
            ['name' => 'Super Admin', 'slug' => Role::SUPER_ADMIN, 'description' => 'CloudKazi HQ System Administrator'],
            ['name' => 'School Admin', 'slug' => Role::SCHOOL_ADMIN, 'description' => 'School IT Administrator & System Manager'],
            ['name' => 'Principal', 'slug' => Role::PRINCIPAL, 'description' => 'School Principal & Head of Institution'],
            ['name' => 'Deputy Principal', 'slug' => Role::DEPUTY_PRINCIPAL, 'description' => 'Deputy Principal & Head of Academics/Discipline'],
            ['name' => 'Bursar', 'slug' => Role::BURSAR, 'description' => 'Finance Officer & Bursar'],
            ['name' => 'Teacher', 'slug' => Role::TEACHER, 'description' => 'Subject Teacher'],
            ['name' => 'Class Teacher', 'slug' => Role::CLASS_TEACHER, 'description' => 'Class / Stream Supervisor Teacher'],
            ['name' => 'Parent', 'slug' => Role::PARENT, 'description' => 'Student Guardian / Parent'],
            ['name' => 'Student', 'slug' => Role::STUDENT, 'description' => 'Enrolled Student'],
            ['name' => 'Librarian', 'slug' => Role::LIBRARIAN, 'description' => 'School Librarian'],
            ['name' => 'Transport Officer', 'slug' => Role::TRANSPORT_OFFICER, 'description' => 'Transport Coordinator'],
            ['name' => 'Hostel Warden', 'slug' => Role::HOSTEL_WARDEN, 'description' => 'Boarding / Hostel Master/Mistress'],
        ];

        $roles = [];
        foreach ($rolesData as $rd) {
            $roles[$rd['slug']] = Role::firstOrCreate(['slug' => $rd['slug']], $rd);
        }

        // 2. Super Admin User
        $superAdmin = User::firstOrCreate(['email' => 'superadmin@cloudkazi.com'], [
            'name' => 'CloudKazi Super Administrator',
            'phone' => '+254700000001',
            'password' => Hash::make('password'),
            'role_id' => $roles[Role::SUPER_ADMIN]->id,
            'is_active' => true,
        ]);

        // 3. Demo login accounts for public browsing and sample dashboards
        $demoAccounts = [
            ['name' => 'Admin Demo', 'email' => 'admin@demo.com', 'role' => Role::SCHOOL_ADMIN],
            ['name' => 'Teacher Demo', 'email' => 'teacher@demo.com', 'role' => Role::TEACHER],
            ['name' => 'Student Demo', 'email' => 'student@demo.com', 'role' => Role::STUDENT],
            ['name' => 'Parent Demo', 'email' => 'parent@demo.com', 'role' => Role::PARENT],
        ];

        foreach ($demoAccounts as $demo) {
            User::updateOrCreate(
                ['email' => $demo['email']],
                [
                    'name' => $demo['name'],
                    'phone' => '+254700000099',
                    'password' => '$2y$12$bSIQSpjatNWWlhwb0U5iQO6ymd.kK3icI6fU/WUKNf0rqVQaDIBLm',
                    'role_id' => $roles[$demo['role']]->id,
                    'school_id' => null,
                    'is_active' => false,
                    'is_demo' => true,
                ]
            );
        }

        // 4. Demo Schools
        $school1 = School::firstOrCreate(['code' => 'NPA-001'], [
            'name' => 'Nairobi Premier Academy',
            'email' => 'info@npa.ac.ke',
            'phone' => '+254711223344',
            'motto' => 'Strive for Excellence and Integrity',
            'address' => 'Kilimani Road, Nairobi',
            'county' => 'Nairobi',
            'curriculum_type' => 'Hybrid',
            'status' => 'active',
            'trial_ends_at' => Carbon::now()->addYear(),
        ]);

        $school2 = School::firstOrCreate(['code' => 'SAH-002'], [
            'name' => 'St. Augustine High School',
            'email' => 'info@staugustine.ac.ke',
            'phone' => '+254722334455',
            'motto' => 'Knowledge is Light',
            'address' => 'Limuru Road, Kiambu',
            'county' => 'Kiambu',
            'curriculum_type' => '8-4-4',
            'status' => 'active',
            'trial_ends_at' => Carbon::now()->addMonths(6),
        ]);

        // Subscriptions & Settings
        foreach ([$school1, $school2] as $sch) {
            Subscription::firstOrCreate(['school_id' => $sch->id], [
                'plan_name' => 'Enterprise School ERP',
                'amount' => 45000.00,
                'status' => 'active',
                'start_date' => Carbon::now()->subMonths(1),
                'end_date' => Carbon::now()->addMonths(11),
                'sms_balance' => 3500,
                'storage_limit_mb' => 10240,
            ]);

            SchoolSetting::firstOrCreate(['school_id' => $sch->id], [
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'daraja_shortcode' => '174379',
                'daraja_consumer_key' => 'TestConsumerKeyCloudKazi',
                'daraja_consumer_secret' => 'TestConsumerSecretCloudKazi',
                'daraja_passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
                'sms_sender_id' => 'CLOUDKAZI',
                'sms_api_key' => 'ck_sms_live_93817293847',
            ]);
        }

        // Setup School 1: Nairobi Premier Academy (Rich Data)
        $sid = $school1->id;

        // Academic Year & Terms
        $year2026 = AcademicYear::firstOrCreate([
            'school_id' => $sid,
            'name' => '2026',
        ], [
            'start_date' => '2026-01-05',
            'end_date' => '2026-11-20',
            'is_current' => true,
        ]);

        $term1 = AcademicTerm::firstOrCreate([
            'school_id' => $sid,
            'academic_year_id' => $year2026->id,
            'name' => 'Term 1',
        ], [
            'start_date' => '2026-01-05',
            'end_date' => '2026-04-10',
            'is_current' => true,
        ]);

        $term2 = AcademicTerm::firstOrCreate([
            'school_id' => $sid,
            'academic_year_id' => $year2026->id,
            'name' => 'Term 2',
        ], [
            'start_date' => '2026-05-04',
            'end_date' => '2026-08-07',
            'is_current' => false,
        ]);

        $term3 = AcademicTerm::firstOrCreate([
            'school_id' => $sid,
            'academic_year_id' => $year2026->id,
            'name' => 'Term 3',
        ], [
            'start_date' => '2026-08-31',
            'end_date' => '2026-10-30',
            'is_current' => false,
        ]);

        // Departments
        $deptSciences = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Sciences']);
        $deptMath = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Mathematics']);
        $deptLanguages = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Languages']);
        $deptHumanities = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Humanities']);
        $deptTechnical = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Technical & Creative Arts']);

        // Classrooms
        $room1 = Classroom::firstOrCreate(['school_id' => $sid, 'name' => 'Room 101 - Simba Wing'], ['type' => 'regular', 'capacity' => 50]);
        $room2 = Classroom::firstOrCreate(['school_id' => $sid, 'name' => 'Room 102 - Chui Wing'], ['type' => 'regular', 'capacity' => 50]);
        $labScience = Classroom::firstOrCreate(['school_id' => $sid, 'name' => 'Main Science Laboratory'], ['type' => 'science_lab', 'capacity' => 45]);
        $labComp = Classroom::firstOrCreate(['school_id' => $sid, 'name' => 'Computer Lab / IT Center'], ['type' => 'computer_lab', 'capacity' => 40]);

        // Classes (CBC Junior & Senior + 8-4-4 Secondary)
        $grade7 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Grade 7'], ['level' => 'junior_school', 'curriculum' => 'CBC', 'numeric_level' => 7]);
        $grade8 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Grade 8'], ['level' => 'junior_school', 'curriculum' => 'CBC', 'numeric_level' => 8]);
        $grade9 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Grade 9'], ['level' => 'junior_school', 'curriculum' => 'CBC', 'numeric_level' => 9]);
        $grade10 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Grade 10'], ['level' => 'senior_school', 'curriculum' => 'CBC', 'numeric_level' => 10]);

        $form1 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Form 1'], ['level' => 'secondary_844', 'curriculum' => '8-4-4', 'numeric_level' => 9]);
        $form2 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Form 2'], ['level' => 'secondary_844', 'curriculum' => '8-4-4', 'numeric_level' => 10]);
        $form3 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Form 3'], ['level' => 'secondary_844', 'curriculum' => '8-4-4', 'numeric_level' => 11]);
        $form4 = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => 'Form 4'], ['level' => 'secondary_844', 'curriculum' => '8-4-4', 'numeric_level' => 12]);

        // Streams
        $streamG7A = Stream::firstOrCreate(['school_id' => $sid, 'school_class_id' => $grade7->id, 'name' => 'East'], ['capacity' => 45]);
        $streamG7B = Stream::firstOrCreate(['school_id' => $sid, 'school_class_id' => $grade7->id, 'name' => 'West'], ['capacity' => 45]);
        $streamG8A = Stream::firstOrCreate(['school_id' => $sid, 'school_class_id' => $grade8->id, 'name' => 'Simba'], ['capacity' => 45]);
        $streamG9A = Stream::firstOrCreate(['school_id' => $sid, 'school_class_id' => $grade9->id, 'name' => 'Alpha'], ['capacity' => 45]);
        $streamF3A = Stream::firstOrCreate(['school_id' => $sid, 'school_class_id' => $form3->id, 'name' => 'East'], ['capacity' => 45]);
        $streamF4A = Stream::firstOrCreate(['school_id' => $sid, 'school_class_id' => $form4->id, 'name' => 'East'], ['capacity' => 45]);

        // Subjects (CBC + 8-4-4)
        $subjectsData = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'curriculum' => 'Both', 'pathway' => 'STEM', 'lessons_per_week' => 5],
            ['name' => 'English', 'code' => 'ENG', 'curriculum' => 'Both', 'pathway' => 'general', 'lessons_per_week' => 5],
            ['name' => 'Kiswahili', 'code' => 'KISW', 'curriculum' => 'Both', 'pathway' => 'general', 'lessons_per_week' => 4],
            ['name' => 'Integrated Science', 'code' => 'INTSCI', 'curriculum' => 'CBC', 'pathway' => 'STEM', 'lessons_per_week' => 4],
            ['name' => 'Social Studies', 'code' => 'SOCST', 'curriculum' => 'CBC', 'pathway' => 'Social Sciences', 'lessons_per_week' => 4],
            ['name' => 'Pre-Technical Studies', 'code' => 'PRETECH', 'curriculum' => 'CBC', 'pathway' => 'STEM', 'lessons_per_week' => 3],
            ['name' => 'Agriculture & Nutrition', 'code' => 'AGRINUT', 'curriculum' => 'CBC', 'pathway' => 'STEM', 'lessons_per_week' => 3],
            ['name' => 'Religious Education', 'code' => 'CRE', 'curriculum' => 'Both', 'pathway' => 'Social Sciences', 'lessons_per_week' => 3],
            ['name' => 'Creative Arts & Sports', 'code' => 'CAS', 'curriculum' => 'CBC', 'pathway' => 'Arts & Sports', 'lessons_per_week' => 3],
            ['name' => 'Biology', 'code' => 'BIO', 'curriculum' => '8-4-4', 'pathway' => 'STEM', 'lessons_per_week' => 4],
            ['name' => 'Chemistry', 'code' => 'CHEM', 'curriculum' => '8-4-4', 'pathway' => 'STEM', 'lessons_per_week' => 4],
            ['name' => 'Physics', 'code' => 'PHY', 'curriculum' => '8-4-4', 'pathway' => 'STEM', 'lessons_per_week' => 4],
            ['name' => 'History & Government', 'code' => 'HIST', 'curriculum' => '8-4-4', 'pathway' => 'Social Sciences', 'lessons_per_week' => 3],
            ['name' => 'Geography', 'code' => 'GEO', 'curriculum' => '8-4-4', 'pathway' => 'Social Sciences', 'lessons_per_week' => 3],
            ['name' => 'Computer Studies', 'code' => 'COMP', 'curriculum' => 'Both', 'pathway' => 'STEM', 'lessons_per_week' => 3],
            ['name' => 'Business Studies', 'code' => 'BUSS', 'curriculum' => '8-4-4', 'pathway' => 'Social Sciences', 'lessons_per_week' => 3],
        ];

        $subjects = [];
        foreach ($subjectsData as $s) {
            $subjects[$s['code']] = Subject::firstOrCreate([
                'school_id' => $sid,
                'code' => $s['code'],
            ], $s);
        }

        // Demo Staff Users & Roles
        $usersToCreate = [
            ['name' => 'Dr. James Kariuki', 'email' => 'admin@npa.ac.ke', 'phone' => '+254722100001', 'role' => Role::SCHOOL_ADMIN],
            ['name' => 'Mrs. Grace Wanjiku', 'email' => 'principal@npa.ac.ke', 'phone' => '+254722100002', 'role' => Role::PRINCIPAL],
            ['name' => 'Mr. Peter Omondi', 'email' => 'deputy@npa.ac.ke', 'phone' => '+254722100003', 'role' => Role::DEPUTY_PRINCIPAL],
            ['name' => 'Mr. David Mutua', 'email' => 'bursar@npa.ac.ke', 'phone' => '+254722100004', 'role' => Role::BURSAR],
            ['name' => 'Mr. Samuel Kiprono', 'email' => 'teacher@npa.ac.ke', 'phone' => '+254722100005', 'role' => Role::TEACHER],
            ['name' => 'Ms. Faith Chebet', 'email' => 'classteacher@npa.ac.ke', 'phone' => '+254722100006', 'role' => Role::CLASS_TEACHER],
            ['name' => 'Mr. Joseph Mwangi', 'email' => 'parent@npa.ac.ke', 'phone' => '+254722100007', 'role' => Role::PARENT],
            ['name' => 'Brian Mwangi', 'email' => 'student@npa.ac.ke', 'phone' => '+254722100008', 'role' => Role::STUDENT],
            ['name' => 'Mrs. Mary Achieng', 'email' => 'librarian@npa.ac.ke', 'phone' => '+254722100009', 'role' => Role::LIBRARIAN],
            ['name' => 'Mr. Hassan Noor', 'email' => 'transport@npa.ac.ke', 'phone' => '+254722100010', 'role' => Role::TRANSPORT_OFFICER],
            ['name' => 'Mr. Boniface Otieno', 'email' => 'warden@npa.ac.ke', 'phone' => '+254722100011', 'role' => Role::HOSTEL_WARDEN],
        ];

        $createdUsers = [];
        foreach ($usersToCreate as $u) {
            $createdUsers[$u['email']] = User::firstOrCreate(['email' => $u['email']], [
                'school_id' => $sid,
                'name' => $u['name'],
                'phone' => $u['phone'],
                'password' => Hash::make('password'),
                'role_id' => $roles[$u['role']]->id,
                'is_active' => true,
            ]);
        }

        // Set Class Teacher for streams
        $streamG7A->update(['class_teacher_id' => $createdUsers['classteacher@npa.ac.ke']->id]);
        $streamG9A->update(['class_teacher_id' => $createdUsers['teacher@npa.ac.ke']->id]);

        // Teachers table entries
        $teacher1 = Teacher::firstOrCreate(['user_id' => $createdUsers['teacher@npa.ac.ke']->id], [
            'school_id' => $sid,
            'staff_no' => 'TSC/482910',
            'tsc_number' => '482910',
            'gender' => 'Male',
            'qualification' => 'B.Ed Mathematics & Physics',
            'designation' => 'Senior Teacher',
            'department_id' => $deptMath->id,
            'specialization' => 'Mathematics & Physics',
            'phone' => '+254722100005',
            'max_weekly_lessons' => 28,
        ]);

        $teacher2 = Teacher::firstOrCreate(['user_id' => $createdUsers['classteacher@npa.ac.ke']->id], [
            'school_id' => $sid,
            'staff_no' => 'TSC/592019',
            'tsc_number' => '592019',
            'gender' => 'Female',
            'qualification' => 'B.Ed English & Literature',
            'designation' => 'Class Teacher (Grade 7)',
            'department_id' => $deptLanguages->id,
            'specialization' => 'English & Literature',
            'phone' => '+254722100006',
            'max_weekly_lessons' => 26,
        ]);

        // Allocations
        TeacherSubjectAllocation::firstOrCreate([
            'school_id' => $sid,
            'teacher_id' => $teacher1->id,
            'subject_id' => $subjects['MATH']->id,
            'school_class_id' => $grade7->id,
            'stream_id' => $streamG7A->id,
        ], ['lessons_per_week' => 5]);

        TeacherSubjectAllocation::firstOrCreate([
            'school_id' => $sid,
            'teacher_id' => $teacher2->id,
            'subject_id' => $subjects['ENG']->id,
            'school_class_id' => $grade7->id,
            'stream_id' => $streamG7A->id,
        ], ['lessons_per_week' => 5]);

        // Guardian
        $parentUser = $createdUsers['parent@npa.ac.ke'];
        $guardian = Guardian::firstOrCreate(['school_id' => $sid, 'phone' => '+254722100007'], [
            'user_id' => $parentUser->id,
            'first_name' => 'Joseph',
            'last_name' => 'Mwangi',
            'email' => 'parent@npa.ac.ke',
            'relationship' => 'Father',
            'occupation' => 'Financial Analyst',
            'id_number' => '24189021',
            'address' => 'Kilimani, Nairobi',
        ]);

        // Students (Grade 7, Grade 9, Form 4)
        $studentUser = $createdUsers['student@npa.ac.ke'];
        $studentBrian = Student::firstOrCreate(['school_id' => $sid, 'admission_number' => 'ADM-2026-001'], [
            'user_id' => $studentUser->id,
            'first_name' => 'Brian',
            'last_name' => 'Mwangi',
            'other_names' => 'Kamau',
            'gender' => 'Male',
            'dob' => '2012-05-14',
            'admission_date' => '2026-01-08',
            'school_class_id' => $grade7->id,
            'stream_id' => $streamG7A->id,
            'curriculum' => 'CBC',
            'pathway' => 'STEM',
            'nemis_upi' => 'UPI-NPA-88219',
            'assessment_number' => 'KNEC-CBC-70192',
            'status' => 'active',
            'medical_conditions' => 'Mild Asthma',
            'blood_group' => 'O+',
        ]);
        $studentBrian->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);

        // Additional demo students
        $demoStudentsData = [
            ['adm' => 'ADM-2026-002', 'fn' => 'Ashley', 'ln' => 'Nekesa', 'on' => 'Wanjala', 'gen' => 'Female', 'dob' => '2012-08-22', 'cls' => $grade7->id, 'st' => $streamG7A->id, 'curr' => 'CBC', 'path' => 'STEM'],
            ['adm' => 'ADM-2026-003', 'fn' => 'Kevin', 'ln' => 'Otieno', 'on' => 'Odhiambo', 'gen' => 'Male', 'dob' => '2012-03-10', 'cls' => $grade7->id, 'st' => $streamG7A->id, 'curr' => 'CBC', 'path' => 'Arts & Sports'],
            ['adm' => 'ADM-2026-004', 'fn' => 'Mercy', 'ln' => 'Chepkoech', 'on' => 'Faith', 'gen' => 'Female', 'dob' => '2011-11-04', 'cls' => $grade8->id, 'st' => $streamG8A->id, 'curr' => 'CBC', 'path' => 'Social Sciences'],
            ['adm' => 'ADM-2026-005', 'fn' => 'Dennis', 'ln' => 'Kipchumba', 'on' => 'Koech', 'gen' => 'Male', 'dob' => '2010-09-18', 'cls' => $grade9->id, 'st' => $streamG9A->id, 'curr' => 'CBC', 'path' => 'STEM'],
            ['adm' => 'ADM-2026-006', 'fn' => 'Priscilla', 'ln' => 'Mutindi', 'on' => 'Mumbua', 'gen' => 'Female', 'dob' => '2008-04-12', 'cls' => $form4->id, 'st' => $streamF4A->id, 'curr' => '8-4-4', 'path' => 'STEM'],
        ];

        $allStudents = [$studentBrian];
        foreach ($demoStudentsData as $ds) {
            $stu = Student::firstOrCreate(['school_id' => $sid, 'admission_number' => $ds['adm']], [
                'first_name' => $ds['fn'],
                'last_name' => $ds['ln'],
                'other_names' => $ds['on'],
                'gender' => $ds['gen'],
                'dob' => $ds['dob'],
                'admission_date' => '2026-01-08',
                'school_class_id' => $ds['cls'],
                'stream_id' => $ds['st'],
                'curriculum' => $ds['curr'],
                'pathway' => $ds['path'],
                'nemis_upi' => 'UPI-' . strtoupper(Str::random(7)),
                'status' => 'active',
            ]);
            $allStudents[] = $stu;
        }

        // Fee Structures & Billing
        $catTuition = FeeCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Tuition & Academic Support']);
        $catBoarding = FeeCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Boarding & Meals']);
        $catActivity = FeeCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Activity & Sports']);
        $catExam = FeeCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Assessment & Examinations']);

        foreach ([$grade7, $grade8, $grade9, $form4] as $cls) {
            FeeStructure::firstOrCreate([
                'school_id' => $sid,
                'academic_year_id' => $year2026->id,
                'term_id' => $term1->id,
                'school_class_id' => $cls->id,
                'fee_category_id' => $catTuition->id,
            ], ['amount' => 25000.00]);

            FeeStructure::firstOrCreate([
                'school_id' => $sid,
                'academic_year_id' => $year2026->id,
                'term_id' => $term1->id,
                'school_class_id' => $cls->id,
                'fee_category_id' => $catActivity->id,
            ], ['amount' => 3500.00]);

            FeeStructure::firstOrCreate([
                'school_id' => $sid,
                'academic_year_id' => $year2026->id,
                'term_id' => $term1->id,
                'school_class_id' => $cls->id,
                'fee_category_id' => $catExam->id,
            ], ['amount' => 2500.00]);
        }

        // Invoices & Payments
        foreach ($allStudents as $stu) {
            $totalFee = 31000.00;
            $paid = $stu->id === $studentBrian->id ? 20000.00 : ($stu->id % 2 === 0 ? 31000.00 : 15000.00);
            $bal = $totalFee - $paid;

            $inv = StudentInvoice::firstOrCreate([
                'school_id' => $sid,
                'student_id' => $stu->id,
                'term_id' => $term1->id,
            ], [
                'invoice_number' => 'INV-2026-' . str_pad($stu->id, 4, '0', STR_PAD_LEFT),
                'academic_year_id' => $year2026->id,
                'total_amount' => $totalFee,
                'paid_amount' => $paid,
                'waiver_amount' => 0.00,
                'balance' => $bal,
                'status' => $bal <= 0 ? 'paid' : 'partially_paid',
                'due_date' => '2026-02-15',
            ]);

            Payment::firstOrCreate([
                'school_id' => $sid,
                'receipt_number' => 'REC-2026-' . str_pad($stu->id, 4, '0', STR_PAD_LEFT),
            ], [
                'student_invoice_id' => $inv->id,
                'student_id' => $stu->id,
                'amount' => $paid,
                'payment_method' => 'mpesa',
                'reference_code' => 'RCL' . strtoupper(Str::random(7)),
                'payment_date' => '2026-01-15',
                'notes' => 'Term 1 Fee Initial Payment via M-Pesa',
            ]);
        }

        // Timetable Generation (Flagship Engine)
        $timetable = Timetable::firstOrCreate([
            'school_id' => $sid,
            'academic_year_id' => $year2026->id,
            'term_id' => $term1->id,
            'name' => '2026 Term 1 Master Schedule',
        ], [
            'type' => 'class',
            'status' => 'published',
            'description' => 'Official Ministry-compliant Master Schedule for Junior & Secondary wings',
        ]);

        $timetableEngine = new TimetableEngineService();
        $timetableEngine->generateStandardSlots($timetable);
        $timetableEngine->autoGenerateAllocations($timetable);

        // Examinations & Report Cards
        $examTerm1Mid = Examination::firstOrCreate([
            'school_id' => $sid,
            'academic_year_id' => $year2026->id,
            'term_id' => $term1->id,
            'name' => 'Term 1 Midterm Assessment',
        ], [
            'curriculum_type' => 'Both',
            'exam_type' => 'midterm',
            'start_date' => '2026-02-23',
            'end_date' => '2026-02-27',
            'is_published' => true,
        ]);

        $assessmentService = new AcademicAssessmentService();

        // 8-4-4 Form 4 marks
        $f4Student = $allStudents[count($allStudents) - 1]; // Priscilla
        $f4Subjects = [$subjects['MATH'], $subjects['ENG'], $subjects['KISW'], $subjects['BIO'], $subjects['CHEM'], $subjects['PHY'], $subjects['HIST']];
        $f4Scores = [84.0, 78.0, 75.0, 88.0, 81.0, 79.0, 86.0];

        foreach ($f4Subjects as $idx => $subj) {
            $sc = $f4Scores[$idx];
            $gradePoints = $assessmentService->get844GradeAndPoints($sc);
            ExamMark844::firstOrCreate([
                'school_id' => $sid,
                'examination_id' => $examTerm1Mid->id,
                'student_id' => $f4Student->id,
                'subject_id' => $subj->id,
            ], [
                'score' => $sc,
                'out_of' => 100.0,
                'grade' => $gradePoints['grade'],
                'points' => $gradePoints['points'],
                'remarks' => $gradePoints['remarks'],
            ]);
        }
        $assessmentService->computeExamSummaries($examTerm1Mid->id, $form4->id);

        // CBC Assessments for Grade 7 & Grade 9
        $cbcSubjects = [$subjects['MATH'], $subjects['ENG'], $subjects['KISW'], $subjects['INTSCI'], $subjects['SOCST'], $subjects['PRETECH']];
        $cbcRubrics = [
            ['strand' => 'Numbers & Algebra', 'sub' => 'Linear Equations', 'level' => 'EE', 'points' => 4, 'comment' => 'Exceeding expectations in logical analysis and problem formulation.'],
            ['strand' => 'Reading & Comprehension', 'sub' => 'Summarization', 'level' => 'ME', 'points' => 3, 'comment' => 'Meets expectations in identifying central themes and narrative arcs.'],
            ['strand' => 'Ufahamu na Sarufi', 'sub' => 'Nomino na Vivumishi', 'level' => 'ME', 'points' => 3, 'comment' => 'Anaonyesha umilisi mzuri wa sarufi na uandishi wa insha.'],
            ['strand' => 'Living Things', 'sub' => 'Cell Structure & Microscope', 'level' => 'EE', 'points' => 4, 'comment' => 'High mastery of lab apparatus and empirical observations.'],
            ['strand' => 'Government & Citizenship', 'sub' => 'Constitution of Kenya', 'level' => 'ME', 'points' => 3, 'comment' => 'Demonstrates deep appreciation of democratic governance and civic duty.'],
            ['strand' => 'Materials & Processing', 'sub' => 'Technical Drawing', 'level' => 'EE', 'points' => 4, 'comment' => 'Precision in orthogonal projections and engineering design.'],
        ];

        foreach ([$studentBrian, $allStudents[1], $allStudents[4]] as $stu) {
            foreach ($cbcSubjects as $i => $subj) {
                $rubric = $cbcRubrics[$i % count($cbcRubrics)];
                CbcAssessment::firstOrCreate([
                    'school_id' => $sid,
                    'examination_id' => $examTerm1Mid->id,
                    'student_id' => $stu->id,
                    'subject_id' => $subj->id,
                    'strand' => $rubric['strand'],
                ], [
                    'sub_strand' => $rubric['sub'],
                    'achievement_level' => $rubric['level'],
                    'score_points' => $rubric['points'],
                    'rubric_feedback' => $rubric['comment'],
                ]);
            }
        }
        $assessmentService->computeExamSummaries($examTerm1Mid->id, $grade7->id);
        $assessmentService->computeExamSummaries($examTerm1Mid->id, $grade9->id);

        // Daily Attendance demo records
        for ($dayOffset = 4; $dayOffset >= 0; $dayOffset--) {
            $attDate = Carbon::now()->subDays($dayOffset)->toDateString();
            foreach ($allStudents as $stu) {
                StudentAttendance::firstOrCreate([
                    'school_id' => $sid,
                    'student_id' => $stu->id,
                    'attendance_date' => $attDate,
                ], [
                    'stream_id' => $stu->stream_id ?: $streamG7A->id,
                    'status' => 'present',
                    'remarks' => 'Punctual',
                ]);
            }

            foreach ([$teacher1, $teacher2] as $tch) {
                TeacherAttendance::firstOrCreate([
                    'school_id' => $sid,
                    'teacher_id' => $tch->id,
                    'attendance_date' => $attDate,
                ], [
                    'check_in_time' => '07:35:00',
                    'check_out_time' => '16:45:00',
                    'status' => 'present',
                ]);
            }
        }

        // Library
        $catSci = BookCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Science & Technology']);
        $catLit = BookCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Literature & Fiction']);
        $catCbc = BookCategory::firstOrCreate(['school_id' => $sid, 'name' => 'CBC Curriculum Guides (KICD)']);

        $b1 = Book::firstOrCreate(['school_id' => $sid, 'title' => 'KLB Top Scholar Junior Secondary Mathematics Grade 7'], [
            'category_id' => $catCbc->id,
            'author' => 'Kenya Literature Bureau (KLB)',
            'isbn' => '978-9966-10-829-1',
            'publisher' => 'KLB Publishers',
            'total_copies' => 120,
            'available_copies' => 114,
            'shelf_location' => 'CBC-Section Shelf 2B',
        ]);

        $b2 = Book::firstOrCreate(['school_id' => $sid, 'title' => 'Blossoms of the Savannah'], [
            'category_id' => $catLit->id,
            'author' => 'Henry R. ole Kulet',
            'isbn' => '978-9966-34-311-2',
            'publisher' => 'Longhorn',
            'total_copies' => 80,
            'available_copies' => 65,
            'shelf_location' => 'Setbook Shelf 1A',
        ]);

        BookIssue::firstOrCreate([
            'school_id' => $sid,
            'book_id' => $b1->id,
            'student_id' => $studentBrian->id,
        ], [
            'issue_date' => Carbon::now()->subDays(5)->toDateString(),
            'due_date' => Carbon::now()->addDays(9)->toDateString(),
            'status' => 'issued',
            'fine_amount' => 0.00,
        ]);

        // Transport
        $route1 = TransportRoute::firstOrCreate(['school_id' => $sid, 'route_name' => 'Route 1 - Westlands / Parklands'], [
            'vehicle_registration' => 'KDA 389M (33 Seater)',
            'driver_name' => 'Hassan Noor',
            'driver_phone' => '+254722100010',
            'termly_fare' => 14000.00,
        ]);

        StudentTransport::firstOrCreate([
            'school_id' => $sid,
            'student_id' => $studentBrian->id,
            'transport_route_id' => $route1->id,
        ], [
            'pickup_point' => 'Sarit Centre Roundabout',
            'is_active' => true,
        ]);

        // Hostel
        $dorm1 = Dormitory::firstOrCreate(['school_id' => $sid, 'name' => 'Mount Kilimanjaro House'], [
            'gender' => 'Male',
            'capacity' => 80,
            'warden_name' => 'Boniface Otieno',
            'warden_phone' => '+254722100011',
        ]);

        $roomH1 = HostelRoom::firstOrCreate(['school_id' => $sid, 'dormitory_id' => $dorm1->id, 'room_number' => 'Cubicle A-1'], ['total_beds' => 4]);

        StudentHostel::firstOrCreate([
            'school_id' => $sid,
            'student_id' => $studentBrian->id,
            'dormitory_id' => $dorm1->id,
        ], [
            'hostel_room_id' => $roomH1->id,
            'bed_number' => 'Bed 2',
        ]);

        // Announcements
        Announcement::firstOrCreate(['school_id' => $sid, 'title' => 'Term 1 Midterm Break & Academic Consultation Day'], [
            'content' => 'Dear Parents and Guardians, the mid-term consultation day will be held on Friday 27th February from 9:00 AM. Academic clinics will be open for all subjects.',
            'target_audience' => 'All',
            'published_at' => Carbon::now()->subDays(2)->toDateString(),
            'created_by' => $createdUsers['principal@npa.ac.ke']->id,
        ]);

        // SMS Logs
        SmsLog::firstOrCreate(['school_id' => $sid, 'recipient_phone' => '+254722100007'], [
            'recipient_name' => 'Joseph Mwangi',
            'message' => 'Nairobi Premier: Received KES 20,000 for Brian Mwangi (ADM-2026-001). Balance: KES 11,000. Thank you.',
            'status' => 'sent',
            'message_type' => 'Fees',
            'cost' => 1.00,
        ]);

        // Discipline Incidents
        DisciplineIncident::firstOrCreate(['school_id' => $sid, 'student_id' => $allStudents[2]->id], [
            'incident_date' => Carbon::now()->subDays(3)->toDateString(),
            'title' => 'Late reporting after lunch break',
            'description' => 'Reported 20 minutes late to the afternoon lesson without permission.',
            'severity' => 'Low',
            'action_taken' => 'Verbal Warning & Cleaned Staffroom Garden',
            'parent_notified' => false,
            'status' => 'resolved',
            'reported_by' => $createdUsers['teacher@npa.ac.ke']->id,
        ]);

        // Inventory Assets
        Asset::firstOrCreate(['school_id' => $sid, 'asset_tag' => 'EQ-LAB-001'], [
            'name' => 'High-Precision Binocular Compound Microscope',
            'category' => 'Lab Equipment',
            'purchase_date' => '2025-03-10',
            'purchase_cost' => 45000.00,
            'condition' => 'Good',
            'location' => 'Main Science Laboratory',
        ]);

        Asset::firstOrCreate(['school_id' => $sid, 'asset_tag' => 'EQ-IT-015'], [
            'name' => 'HP ProDesk 400 Core i5 Desktop Computer',
            'category' => 'IT & Computers',
            'purchase_date' => '2025-06-12',
            'purchase_cost' => 62000.00,
            'condition' => 'New',
            'location' => 'Computer Lab / IT Center',
        ]);

        // Employees & Payroll
        $emp1 = Employee::firstOrCreate(['school_id' => $sid, 'employee_no' => 'EMP-2026-001'], [
            'user_id' => $createdUsers['bursar@npa.ac.ke']->id,
            'first_name' => 'David',
            'last_name' => 'Mutua',
            'id_number' => '21908412',
            'kra_pin' => 'A009182736Z',
            'nssf_no' => 'NSSF-77182',
            'nhif_no' => 'NHIF-99120',
            'designation' => 'Senior Bursar & Finance Officer',
            'department' => 'Finance & Accounts',
            'hire_date' => '2023-02-01',
            'basic_salary' => 85000.00,
            'status' => 'active',
        ]);

        PayrollRecord::firstOrCreate(['school_id' => $sid, 'employee_id' => $emp1->id, 'month' => 2, 'year' => 2026], [
            'basic_salary' => 85000.00,
            'allowances' => 15000.00,
            'deductions' => 19500.00,
            'net_salary' => 80500.00,
            'status' => 'paid',
            'payment_date' => '2026-02-28',
        ]);

        // Learning Materials
        LearningMaterial::firstOrCreate(['school_id' => $sid, 'title' => 'Grade 7 Integrated Science - Cellular Organization & Microscope Notes'], [
            'school_class_id' => $grade7->id,
            'subject_id' => $subjects['INTSCI']->id,
            'teacher_id' => $teacher1->id,
            'description' => 'Comprehensive study notes with lab diagrams, self-assessment questions, and practical activities for Strand 1.',
            'type' => 'notes',
        ]);

        LearningMaterial::firstOrCreate(['school_id' => $sid, 'title' => 'Mathematics Grade 7 - Term 1 Algebraic Expressions Assignment'], [
            'school_class_id' => $grade7->id,
            'subject_id' => $subjects['MATH']->id,
            'teacher_id' => $teacher1->id,
            'description' => 'Solve problems 1 to 25 from Chapter 4. Submit before the scheduled due date.',
            'type' => 'assignment',
            'due_date' => Carbon::now()->addDays(4)->toDateString(),
        ]);

        $this->call([
            LegalDocumentSeeder::class,
            HelpArticleSeeder::class,
        ]);
    }
}
