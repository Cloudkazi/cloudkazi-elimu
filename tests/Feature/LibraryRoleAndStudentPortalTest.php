<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookIssue;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LibraryRoleAndStudentPortalTest extends TestCase
{
    use DatabaseTransactions;

    protected School $school;
    protected User $adminUser;
    protected User $librarianUser;
    protected Student $studentA;
    protected Student $studentB;
    protected User $studentAUser;
    protected User $studentBUser;
    protected Book $book1;
    protected Book $book2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::firstOrCreate(['code' => 'NPA-001'], [
            'name' => 'Nairobi Premier Academy',
            'status' => 'active',
        ]);

        $librarianRole = Role::where('slug', Role::LIBRARIAN)->firstOrFail();
        $adminRole = Role::where('slug', Role::SCHOOL_ADMIN)->firstOrFail();
        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();

        // 1. Admin user
        $this->adminUser = User::firstOrCreate(['email' => 'testadmin@npa.ac.ke'], [
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Test School Admin',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 2. Librarian user
        $this->librarianUser = User::firstOrCreate(['email' => 'testlibrarian@npa.ac.ke'], [
            'school_id' => $this->school->id,
            'role_id' => $librarianRole->id,
            'name' => 'Test Librarian User',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // 3. Students
        $this->studentA = Student::firstOrCreate(['admission_number' => 'ADM-TEST-001'], [
            'school_id' => $this->school->id,
            'first_name' => 'Alice',
            'last_name' => 'Wanjiku',
            'status' => 'active',
        ]);

        $this->studentAUser = User::firstOrCreate(['email' => 'alice@student.cloudkazi.com'], [
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Alice Wanjiku',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->studentA->update(['user_id' => $this->studentAUser->id]);

        $this->studentB = Student::firstOrCreate(['admission_number' => 'ADM-TEST-002'], [
            'school_id' => $this->school->id,
            'first_name' => 'Bob',
            'last_name' => 'Otieno',
            'status' => 'active',
        ]);

        $this->studentBUser = User::firstOrCreate(['email' => 'bob@student.cloudkazi.com'], [
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Bob Otieno',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->studentB->update(['user_id' => $this->studentBUser->id]);

        // 4. Books
        $category = BookCategory::firstOrCreate(['school_id' => $this->school->id, 'name' => 'Literature'], [
            'description' => 'Literary Works',
        ]);

        $this->book1 = Book::firstOrCreate(['school_id' => $this->school->id, 'title' => 'The River and the Source'], [
            'author' => 'Margaret Ogola',
            'category_id' => $category->id,
            'total_copies' => 10,
            'available_copies' => 10,
        ]);

        $this->book2 = Book::firstOrCreate(['school_id' => $this->school->id, 'title' => 'Fathers of Nations'], [
            'author' => 'Paul B. Vitta',
            'category_id' => $category->id,
            'total_copies' => 10,
            'available_copies' => 10,
        ]);
    }

    public function test_librarian_role_helper_methods_and_admin_exclusion()
    {
        $this->assertTrue($this->librarianUser->isLibrarian());
        $this->assertFalse($this->librarianUser->isAdmin());
        $this->assertFalse($this->librarianUser->isSchoolAdmin());
        $this->assertFalse($this->librarianUser->isPrincipal());
        $this->assertFalse($this->librarianUser->isSuperAdmin());

        $this->assertStringContainsString('/library', $this->librarianUser->getDashboardRoute());
    }

    public function test_creating_librarian_user_persists_librarian_role_id_only()
    {
        $librarianRole = Role::where('slug', Role::LIBRARIAN)->firstOrFail();

        $this->actingAs($this->adminUser)
            ->withSession(['active_school_id' => $this->school->id])
            ->post('/school/users', [
                'name' => 'New Junior Librarian',
                'email' => 'juniorlib@npa.ac.ke',
                'phone' => '+254711998877',
                'role_id' => $librarianRole->id,
                'password' => 'Staff@2026!',
            ])
            ->assertRedirect();

        $createdUser = User::where('email', 'juniorlib@npa.ac.ke')->firstOrFail();
        $this->assertEquals($librarianRole->id, $createdUser->role_id);
        $this->assertTrue($createdUser->isLibrarian());
        $this->assertFalse($createdUser->isAdmin());
        $this->assertFalse($createdUser->isSchoolAdmin());
    }

    public function test_librarian_cannot_access_admin_modules()
    {
        $adminRoutes = [
            '/dashboard',
            '/school/dashboard',
            '/school/users',
            '/students',
            '/students/create',
            '/finance',
            '/academics/classes',
            '/examinations',
            '/operations/transport',
            '/operations/payroll',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->actingAs($this->librarianUser)
                ->withSession(['active_school_id' => $this->school->id])
                ->get($route);

            $this->assertEquals(403, $response->getStatusCode(), "Librarian should receive 403 on {$route}");
        }
    }

    public function test_librarian_can_access_library_modules()
    {
        $libraryRoutes = [
            '/library',
            '/library/dashboard',
            '/library/books',
            '/library/categories',
            '/library/issue',
            '/library/returns',
            '/library/history',
            '/library/reports',
        ];

        foreach ($libraryRoutes as $route) {
            $response = $this->actingAs($this->librarianUser)
                ->withSession(['active_school_id' => $this->school->id])
                ->get($route);

            $this->assertEquals(200, $response->getStatusCode(), "Librarian should access {$route}");
        }
    }

    public function test_student_portal_displays_allocated_books_and_isolates_other_students_books()
    {
        // Issue active on-time book to Student A
        BookIssue::create([
            'school_id' => $this->school->id,
            'book_id' => $this->book1->id,
            'student_id' => $this->studentA->id,
            'issue_date' => Carbon::now()->subDays(2)->toDateString(),
            'due_date' => Carbon::now()->addDays(12)->toDateString(),
            'status' => 'issued',
        ]);

        // Issue overdue book to Student A
        BookIssue::create([
            'school_id' => $this->school->id,
            'book_id' => $this->book2->id,
            'student_id' => $this->studentA->id,
            'issue_date' => Carbon::now()->subDays(20)->toDateString(),
            'due_date' => Carbon::now()->subDays(6)->toDateString(),
            'status' => 'issued',
        ]);

        // Login as Student A
        $responseA = $this->actingAs($this->studentAUser)
            ->withSession(['active_school_id' => $this->school->id])
            ->get('/portal/student');

        $responseA->assertStatus(200);
        $responseA->assertSee('My Library Books');
        $responseA->assertSee('The River and the Source');
        $responseA->assertSee('Fathers of Nations');
        $responseA->assertSee('Overdue');

        // Login as Student B (No books issued)
        $responseB = $this->actingAs($this->studentBUser)
            ->withSession(['active_school_id' => $this->school->id])
            ->get('/portal/student');

        $responseB->assertStatus(200);
        $responseB->assertSee('My Library Books');
        $responseB->assertSee('No library books have been issued to you.');
        $responseB->assertDontSee('The River and the Source');
        $responseB->assertDontSee('Fathers of Nations');
    }
}
