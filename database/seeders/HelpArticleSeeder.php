<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

class HelpArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            // ADMISSIONS
            [
                'category'  => 'Admissions',
                'question'  => 'How do I register a student?',
                'answer'    => 'To register a new student, navigate to Students > Admissions > Add Student. Complete the student biodata, select their class and stream, enter parent/guardian emergency contact details, and click "Submit Admission". The system will automatically generate a unique admission number and create the student record.',
                'keywords'  => 'register student, add student, admission, new student, enroll student, student enrollment, admit student',
            ],
            [
                'category'  => 'Admissions',
                'question'  => 'How do I import students in bulk via Excel or CSV?',
                'answer'    => 'Go to Students > Student List and click the "Import Students" button. Download the standard CSV/Excel template, fill in the required columns (First Name, Last Name, Gender, Class, Stream, Parent Phone), and upload the completed file. The platform will validate rows and enroll all students in a single batch.',
                'keywords'  => 'import students, bulk admission, upload student list, excel import, csv upload, batch students',
            ],
            [
                'category'  => 'Admissions',
                'question'  => 'How do I promote students to the next class or grade?',
                'answer'    => 'Navigate to Students > Promotions. Select the source academic year and current class (e.g., Grade 7 East), then select the target class and new academic year (e.g., Grade 8 East). Review the student list, uncheck any repeating students, and click "Promote Selected Students".',
                'keywords'  => 'promote students, class promotion, academic progression, year rollover, grade upgrade, promote class',
            ],

            // FINANCE
            [
                'category'  => 'Finance',
                'question'  => 'How do I record fees?',
                'answer'    => 'Navigate to Finance > Fee Payments. Click "Record Payment", search for the student by name or admission number, select the payment mode (M-Pesa, Cash, Bank Slip, Cheque), enter the amount paid, payment reference/receipt number, and click "Save Payment". A digital receipt is generated immediately and can be printed or sent via SMS.',
                'keywords'  => 'record fees, fee payment, pay fees, receive fee, bursar payment, fee collection, enter payment, fee receipt',
            ],
            [
                'category'  => 'Finance',
                'question'  => 'How does M-Pesa Daraja payment integration work?',
                'answer'    => 'CloudKazi Elimu integrates directly with Safaricom Daraja API. When parents pay via Paybill or Lipa na M-Pesa using the student admission number as account number, Safaricom sends an instant callback to the system. The platform verifies the transaction, auto-credits the student fee account, updates the balance, and logs the M-Pesa reference code without requiring manual bursar data entry.',
                'keywords'  => 'mpesa, daraja, safaricom, paybill, lipa na mpesa, automatic payment, stk push, mpesa callback',
            ],
            [
                'category'  => 'Finance',
                'question'  => 'How do I create or edit a fee structure for a term?',
                'answer'    => 'Go to Finance > Fee Structures. Select the Academic Year, Term, and Class Level. Click "Add Fee Structure", add fee vote heads (Tuition, Boarding, Activity, Examination, Maintenance), specify the amount for each category, and click "Save Structure". You can also apply structures across multiple streams.',
                'keywords'  => 'fee structure, fee categories, tuition fee, vote heads, term fees, boarding fee, set fees',
            ],
            [
                'category'  => 'Finance',
                'question'  => 'How do I generate student fee invoices?',
                'answer'    => 'Go to Finance > Invoices > Generate Invoices. Select the target academic year, term, and classes. Click "Batch Generate Invoices". The system applies the active fee structure for each class, creates individual debit entries for enrolled students, and sends balance summaries to parents.',
                'keywords'  => 'generate invoices, fee invoice, bill students, fee billing, term invoice, student bill',
            ],

            // EXAMS
            [
                'category'  => 'Exams',
                'question'  => 'How do I generate report cards?',
                'answer'    => 'Navigate to Examinations > Report Cards. Select the Academic Year, Term, Class, and Stream. Choose the examination session (e.g., Term 1 Final), select the curriculum format (CBC Rubric or 8-4-4 Marks & Positions), and click "Generate Report Cards". You can preview individual student reports or click "Download All as PDF" for bulk printing.',
                'keywords'  => 'generate report cards, report card, student results, print report forms, examination results, academic report, cbc report card',
            ],
            [
                'category'  => 'Exams',
                'question'  => 'How do I enter marks for CBC assessments?',
                'answer'    => 'Go to Examinations > CBC Assessments. Select the Grade, Stream, Subject, and Strand/Sub-strand. In the assessment grid, click the appropriate rubric level for each learner: Exceeding Expectation (EE - 4), Meeting Expectation (ME - 3), Approaching Expectation (AE - 2), or Below Expectation (BE - 1). The system auto-saves each score upon selection.',
                'keywords'  => 'cbc assessment, enter marks, cbc grading, rubrics, exceeding expectation, meeting expectation, formative assessment, summative',
            ],
            [
                'category'  => 'Exams',
                'question'  => 'How do I record 8-4-4 numerical examination marks?',
                'answer'    => 'Navigate to Examinations > Marks Entry. Select the exam session, class, and subject. Enter the numerical score (0 to 100) for each student. The system computes grade letters (A, A-, B+, etc.), points (1 to 12), and subject rank automatically based on the school grading scale.',
                'keywords'  => '8-4-4 marks, exam marks entry, numerical score, grade scale, mean score, points, kcpe, kcse grading',
            ],

            // TIMETABLE
            [
                'category'  => 'Timetable',
                'question'  => 'How do I generate automatic timetables?',
                'answer'    => 'Navigate to Timetable > Automatic Generator. Configure your school timetable rules (periods per day, break and lunch timings, maximum consecutive periods per teacher). Click "Run AI Timetable Generation". The system’s constraint satisfaction engine will allocate teachers, subjects, and classrooms without period collisions or double-booking.',
                'keywords'  => 'timetable generator, automatic timetable, create timetable, class schedule, schedule lessons, timetable collision, lesson periods',
            ],
            [
                'category'  => 'Timetable',
                'question'  => 'How do I resolve teacher timetable collisions?',
                'answer'    => 'Go to Timetable > Master Timetable or Timetable Allocations. Filter by Teacher to view their schedule across all streams. Any conflicting slots are highlighted with a red warning badge. Click on the conflicting slot and drag it to an available period or assign a substitute teacher.',
                'keywords'  => 'timetable clash, teacher collision, double booking, period conflict, fix timetable, reschedule lesson',
            ],

            // TEACHERS
            [
                'category'  => 'Teachers',
                'question'  => 'How do I assign subjects to teachers?',
                'answer'    => 'Go to Academics > Teacher Subject Allocations. Select the Teacher, then choose their designated Class, Stream, and Subject (e.g., Mathematics - Grade 7 East). Click "Assign Subject". The assigned teacher will immediately gain permission to enter marks, upload learning materials, and mark attendance for that class.',
                'keywords'  => 'assign teacher, teacher subjects, subject allocation, assign class, teacher stream, subject teacher',
            ],
            [
                'category'  => 'Teachers',
                'question'  => 'How do I take staff attendance?',
                'answer'    => 'Navigate to Attendance > Staff Attendance. Select the date and department. Mark each teacher or staff member as Present, Absent, Late, or On Leave, then click "Submit Attendance". Attendance statistics appear in real time on the Principal dashboard.',
                'keywords'  => 'teacher attendance, staff attendance, mark attendance, teacher roll call, staff presence',
            ],

            // PARENTS
            [
                'category'  => 'Parents',
                'question'  => 'How do parents access the parent portal?',
                'answer'    => 'Parents can log in from the main login page using their registered phone number or email address. Once logged in, they can view their children’s academic performance, CBC assessments, fee statement and balance, download report cards, and initiate instant M-Pesa fee payments.',
                'keywords'  => 'parent portal, parent login, parent access, check child results, view fee balance, parent account',
            ],
            [
                'category'  => 'Parents',
                'question'  => 'How do I send SMS notifications or announcements to parents?',
                'answer'    => 'Navigate to Operations > Notices or Communication > Send SMS. Choose your recipient group (All Parents, Specific Class, or Parents with Fee Balances). Type your message or select an announcement template, and click "Send SMS". Delivery reports and SMS credit balance are tracked automatically.',
                'keywords'  => 'send sms, bulk sms, parent notice, school announcement, message parents, fee reminder sms',
            ],

            // STUDENTS
            [
                'category'  => 'Students',
                'question'  => 'How do students log into the student portal?',
                'answer'    => 'Students access the system via the login page using their Admission Number as the username and their assigned password (or admission number as default password for first-time login). In the student portal, learners can access notes, assignments, class timetable, and library book records.',
                'keywords'  => 'student login, student portal, student access, admission number login, student assignments, student notes',
            ],
            [
                'category'  => 'Students',
                'question'  => 'How do teachers upload learning materials and notes for students?',
                'answer'    => 'Teachers can navigate to Academics > Learning Materials > Upload Material. Enter the title, select the Class, Subject, and Material Type (Notes, Assignment, Past Paper, or Video link), attach the PDF or document file, and click "Publish". Students enrolled in that class receive access instantly.',
                'keywords'  => 'learning materials, upload notes, share assignment, homework, past papers, course materials',
            ],

            // REPORTS
            [
                'category'  => 'Reports',
                'question'  => 'How do I download attendance reports?',
                'answer'    => 'Go to Attendance > Attendance Reports. Select the date range, class, and stream. The system displays daily percentages, chronic absentee alerts, and gender breakdown. Click "Export PDF" or "Export Excel" to save the official register.',
                'keywords'  => 'attendance report, download attendance, export register, student attendance summary, roll call sheet',
            ],
            [
                'category'  => 'Reports',
                'question'  => 'How do I export fee collection summaries to Excel or PDF?',
                'answer'    => 'Navigate to Finance > Financial Reports. Select the Term, Fee Category, and Date Range. You can generate Fee Collection Summaries, Defaulters List, or M-Pesa Reconciliation Reports and click "Export to Excel" or "Download PDF".',
                'keywords'  => 'fee report, collection summary, fee defaulters, fee balance list, finance export, financial statements',
            ],

            // SYSTEM SETTINGS
            [
                'category'  => 'System Settings',
                'question'  => 'How do I update school terms and academic years?',
                'answer'    => 'Navigate to Academics > Academic Years & Terms. Here you can set the Current Active Academic Year (e.g., 2026), define Term opening and closing dates, and mark the active term. All curriculum assessments and fee structures link directly to the designated active term.',
                'keywords'  => 'academic year, school terms, term dates, active term, new school year, academic calendar',
            ],
            [
                'category'  => 'System Settings',
                'question'  => 'How do I change my password?',
                'answer'    => 'Click on your user avatar or name in the top right corner and select "Change Password". Enter your current password followed by your new password (minimum 8 characters, including a number and special character), and click "Update Password".',
                'keywords'  => 'change password, reset password, update password, account security, forgot password',
            ],
            [
                'category'  => 'System Settings',
                'question'  => 'How do I add or manage user accounts and staff roles?',
                'answer'    => 'Navigate to Settings > Staff & Accounts or Users. Click "Create User", enter the staff member details (Name, Email, Phone), and assign their role (School Admin, Principal, Bursar, Teacher, Librarian). An invitation with temporary login credentials will be prepared.',
                'keywords'  => 'create user, add staff, user roles, permissions, bursar account, teacher account, admin privileges',
            ],

            // LIBRARY
            [
                'category'  => 'Library',
                'question'  => 'How do I issue a library book to a student or teacher?',
                'answer'    => 'Go to Operations > Library > Issue Books. Search for the book by title or ISBN barcode, select the student or teacher borrower, set the return due date, and click "Issue Book". The system updates available copies and tracks the loan status.',
                'keywords'  => 'issue book, library, borrow book, book catalog, library management, checkout book',
            ],
            [
                'category'  => 'Library',
                'question'  => 'How do I return library books and track overdue fines?',
                'answer'    => 'Go to Operations > Library > Return Books. Scan or select the issued book transaction, click "Mark Returned". If the book is returned past the due date, the system calculates overdue daily fines automatically and can post the charge to the student’s fee account.',
                'keywords'  => 'return book, library fines, overdue book, overdue charge, book check-in',
            ],

            // OPERATIONS
            [
                'category'  => 'Operations',
                'question'  => 'How do I assign students to transport routes and dormitories?',
                'answer'    => 'Navigate to Operations > Transport to manage routes, pickup zones, and vehicle driver allocations. For boarding schools, navigate to Operations > Hostel to create dormitories, configure room capacities, and assign student boarding allocations.',
                'keywords'  => 'transport route, school bus, hostel, dormitory, boarding allocation, room assignment, bus pickup',
            ],
        ];

        foreach ($articles as $data) {
            HelpArticle::updateOrCreate(
                ['question' => $data['question']],
                [
                    'category'   => $data['category'],
                    'answer'     => $data['answer'],
                    'keywords'   => $data['keywords'],
                    'is_active'  => true,
                    'view_count' => rand(10, 85),
                ]
            );
        }
    }
}
