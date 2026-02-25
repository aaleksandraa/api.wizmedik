<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    DoctorController,
    AppointmentController,
    AdminController,
    ClinicController,
    CityController,
    SpecialtyController,
    ServiceController,
    UploadController,
    RecenzijaController,
    SettingsController,
    HomepageController,
    NotifikacijaController,
    DoctorDashboardController,
    LogoSettingsController,
    CalendarSyncController,
    RegistrationController,
    MedicalCalendarController
};
use App\Http\Controllers\HealthCheckController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoints (no authentication required)
Route::get('/health', [HealthCheckController::class, 'check']);
Route::get('/ping', [HealthCheckController::class, 'ping']);

// Public routes with rate limiting
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,60'); // 5 attempts per 60 minutes
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,5'); // 10 attempts per 5 minutes
Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:3,60'); // 3 attempts per 60 minutes
Route::post('/password/reset', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,60'); // 5 attempts per 60 minutes

// Test email endpoint (local only)
Route::post('/test-email', [AuthController::class, 'testEmail']);

// Registration routes (for different entity types) with bot detection
Route::middleware(['throttle:5,60', 'detect.bots'])->group(function () {
    Route::post('/register/doctor', [RegistrationController::class, 'registerDoctor']);
    Route::post('/register/clinic', [RegistrationController::class, 'registerClinic']);
    Route::post('/register/laboratory', [RegistrationController::class, 'registerLaboratory']);
    Route::post('/register/spa', [RegistrationController::class, 'registerSpa']);
    Route::post('/register/care-home', [RegistrationController::class, 'registerCareHome']);
});

// Email verification routes
Route::get('/verify-email/{token}', [RegistrationController::class, 'verifyEmail']);
Route::get('/register/verify/{token}', [RegistrationController::class, 'verifyEmail']); // Alias for email link
Route::post('/verify-email-code', [RegistrationController::class, 'verifyEmailWithCode'])
    ->middleware('throttle:10,60');
Route::post('/register/verify-code', [RegistrationController::class, 'verifyEmailWithCode']); // Alias
Route::post('/resend-verification', [RegistrationController::class, 'resendVerification'])
    ->middleware('throttle:3,60');
Route::post('/register/resend-verification', [RegistrationController::class, 'resendVerification'])
    ->middleware('throttle:3,60'); // Alias
Route::get('/register/settings', [RegistrationController::class, 'getSettings']);

// Public doctor routes
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/slug/{slug}', [DoctorController::class, 'show']);
// Specific routes MUST come before wildcard {id} route
Route::get('/doctors/{id}/available-slots', [DoctorController::class, 'availableSlots'])->where('id', '[0-9]+');
Route::get('/doctors/{id}/booked-slots', [DoctorController::class, 'bookedSlots'])->where('id', '[0-9]+');
Route::get('/doctors/{id}/guest-visits', [DoctorController::class, 'publicGuestVisits'])->where('id', '[0-9]+');
Route::get('/doctors/{id}/services', [DoctorController::class, 'getServices'])->where('id', '[0-9]+');
// Wildcard route MUST be last - only matches numeric IDs
Route::get('/doctors/{id}', [DoctorController::class, 'showById'])->where('id', '[0-9]+');

// Public appointment routes (guest booking)
Route::post('/appointments/guest', [AppointmentController::class, 'storeGuest']);

// Public calendar sync route (iCal feed)
Route::get('/calendar/ical/{token}.ics', [CalendarSyncController::class, 'generateICalFeed']);
Route::get('/calendar/ical/{token}', [CalendarSyncController::class, 'generateICalFeed']);

// Public lookup routes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{slug}', [CityController::class, 'show']);
Route::get('/clinics', [ClinicController::class, 'index']);
Route::get('/clinics/{slug}', [ClinicController::class, 'show']);
Route::get('/specialties', [SpecialtyController::class, 'index']);
Route::get('/specialties/with-counts', [SpecialtyController::class, 'withCounts']);
Route::get('/specialties/search-data', [SpecialtyController::class, 'searchData']);
Route::get('/specialties/smart-search/{query}', [SpecialtyController::class, 'smartSearch']);
Route::get('/specialties/{slug}', [SpecialtyController::class, 'show']);

// Homepage data
Route::get('/homepage', [HomepageController::class, 'getData']);
Route::post('/homepage/clear-cache', [HomepageController::class, 'clearCache']);

// Settings (public)
Route::get('/settings/templates', [SettingsController::class, 'getTemplates']);
Route::get('/settings/doctor-card', [SettingsController::class, 'getDoctorCardSettings']);
Route::get('/settings/clinic-card', [SettingsController::class, 'getClinicCardSettings']);
Route::get('/settings/homepage', [SettingsController::class, 'getHomepageSettings']);
Route::get('/settings/colors', [SettingsController::class, 'getGlobalColors']);
Route::get('/settings/specialty-template', [SettingsController::class, 'getSpecialtyTemplate']);
Route::get('/settings/blog-typography', [SettingsController::class, 'getBlogTypography']);
Route::get('/settings/listing-template', [SettingsController::class, 'getListingTemplate']);
Route::get('/settings/cookie', [SettingsController::class, 'getCookieSettings']);
Route::get('/settings/privacy-policy', [SettingsController::class, 'getPrivacyPolicy']);
Route::get('/settings/terms-of-service', [SettingsController::class, 'getTermsOfService']);

// Logo settings (public)
Route::get('/logo-settings', [LogoSettingsController::class, 'index']);

// Public reviews (using RecenzijaController)
Route::get('/recenzije/doktor/{doktorId}', [RecenzijaController::class, 'getByDoktor']);
Route::get('/recenzije/klinika/{klinikaId}', [RecenzijaController::class, 'getByKlinika']);
Route::get('/recenzije/{type}/{id}/stats', [RecenzijaController::class, 'getRatingStats']);

// Blog public routes
Route::get('/blog', [\App\Http\Controllers\Api\BlogController::class, 'index']);
Route::get('/blog/homepage', [\App\Http\Controllers\Api\BlogController::class, 'homepage']);
Route::get('/blog/categories', [\App\Http\Controllers\Api\BlogController::class, 'categories']);
Route::get('/blog/authors', [\App\Http\Controllers\Api\BlogController::class, 'authors']);
Route::get('/blog/doctor/{doctorSlug}', [\App\Http\Controllers\Api\BlogController::class, 'doctorPosts']);
Route::get('/blog/{slug}', [\App\Http\Controllers\Api\BlogController::class, 'show'])
    // Prevent route conflict with authenticated doctor blog endpoints.
    ->where('slug', '^(?!my-posts$|can-write$|can-doctors-write$).+');

// Public pitanja (Q&A) routes with rate limiting
Route::get('/pitanja', [\App\Http\Controllers\Api\PitanjeController::class, 'index']);
Route::get('/pitanja/tagovi/popularni', [\App\Http\Controllers\Api\PitanjeController::class, 'popularniTagovi']);
Route::get('/pitanja/{slug}', [\App\Http\Controllers\Api\PitanjeController::class, 'show']);
Route::post('/pitanja', [\App\Http\Controllers\Api\PitanjeController::class, 'store'])
    ->middleware('throttle:5,60'); // 5 questions per 60 minutes
Route::post('/pitanja/{id}/odgovori', [\App\Http\Controllers\Api\PitanjeController::class, 'odgovori'])->middleware('auth:sanctum');
Route::post('/pitanja/odgovori/{id}/lajk', [\App\Http\Controllers\Api\PitanjeController::class, 'lajkujOdgovor'])
    ->middleware('throttle:20,5'); // 20 likes per 5 minutes

// Public domovi (care homes) routes with rate limiting
Route::get('/domovi-njega', [\App\Http\Controllers\Api\DomController::class, 'index']);
Route::get('/domovi-njega/filter-options', [\App\Http\Controllers\Api\DomController::class, 'filterOptions']);
Route::get('/domovi-njega/grad/{grad}', [\App\Http\Controllers\Api\DomController::class, 'poGradu']);
Route::get('/domovi-njega/{slug}', [\App\Http\Controllers\Api\DomController::class, 'show']);
Route::post('/domovi-njega/{id}/upit', [\App\Http\Controllers\Api\DomController::class, 'posaljiUpit'])
    ->middleware('throttle:5,60'); // 5 inquiries per 60 minutes
Route::post('/domovi-njega/{id}/recenzija', [\App\Http\Controllers\Api\DomController::class, 'dodajRecenziju'])
    ->middleware('throttle:10,60'); // 10 reviews per 60 minutes

// Public banje (spas) routes with rate limiting
Route::get('/banje', [\App\Http\Controllers\Api\BanjaController::class, 'index']);
Route::get('/banje/search', [\App\Http\Controllers\Api\BanjaController::class, 'index']); // Alias
Route::get('/banje/filter-options', [\App\Http\Controllers\Api\BanjaController::class, 'filterOptions']);
Route::get('/banje/grad/{grad}', [\App\Http\Controllers\Api\BanjaController::class, 'poGradu']);
Route::get('/banje/{id}/paketi', [\App\Http\Controllers\Api\BanjaController::class, 'getPaketi'])->where('id', '[0-9]+');
Route::get('/banje/{id}/recenzije', [\App\Http\Controllers\Api\BanjaController::class, 'getRecenzije'])->where('id', '[0-9]+');
Route::get('/banje/{slug}', [\App\Http\Controllers\Api\BanjaController::class, 'show']);
Route::post('/banje/{id}/upit', [\App\Http\Controllers\Api\BanjaController::class, 'posaljiUpit'])
    ->middleware('throttle:5,60'); // 5 inquiries per 60 minutes
Route::post('/banje/{id}/recenzija', [\App\Http\Controllers\Api\BanjaController::class, 'dodajRecenziju'])
    ->middleware('throttle:10,60'); // 10 reviews per 60 minutes

// Public laboratory routes
Route::get('/laboratorije', [\App\Http\Controllers\Api\LaboratorijaController::class, 'index']);
Route::get('/laboratorije/gradovi/all', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getGradovi']);

// Public MKB-10 routes
Route::get('/mkb10/kategorije', [\App\Http\Controllers\Api\Mkb10Controller::class, 'kategorije']);
Route::get('/mkb10/statistika', [\App\Http\Controllers\Api\Mkb10Controller::class, 'statistika']);
Route::get('/mkb10/search', [\App\Http\Controllers\Api\Mkb10Controller::class, 'pretraga']);
Route::get('/mkb10/dijagnoze', [\App\Http\Controllers\Api\Mkb10Controller::class, 'dijagnoze']);
Route::get('/mkb10/dijagnoze/{kod}', [\App\Http\Controllers\Api\Mkb10Controller::class, 'dijagnoza']);
Route::get('/mkb10/podkategorije/{kategorijaId}', [\App\Http\Controllers\Api\Mkb10Controller::class, 'podkategorije']);
Route::get('/mkb10/settings', [\App\Http\Controllers\Api\Mkb10Controller::class, 'settings']);

Route::get('/laboratorije/kategorije/all', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getKategorije']);
Route::get('/laboratorije/statistics', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getAllStatistics']);
Route::get('/laboratorije/popularne-analize', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getPopularneAnalize']);
Route::get('/laboratorije/analize-na-akciji', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getAnalizenaAkciji']);
Route::get('/laboratorije/search-analize', [\App\Http\Controllers\Api\LaboratorijaController::class, 'searchAnalize']);
Route::get('/laboratorije/grad/{grad}', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getByGrad']);
Route::get('/laboratorije/{slug}', [\App\Http\Controllers\Api\LaboratorijaController::class, 'show']);
Route::get('/laboratorije/{id}/analize', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getAnalize']);
Route::get('/laboratorije/{id}/paketi', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getPaketi']);
Route::get('/laboratorije/{id}/statistics', [\App\Http\Controllers\Api\LaboratorijaController::class, 'getStatistics']);

// Laboratory reviews (public)
Route::get('/laboratorije/{laboratorijaId}/recenzije', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'index']);
Route::get('/laboratorije/{laboratorijaId}/recenzije/stats', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'stats']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Doctor Dashboard (doctor or admin only)
    Route::middleware('doctor')->group(function () {
        Route::get('/doctors/me/profile', [DoctorController::class, 'myProfile']);
        Route::put('/doctors/me/profile', [DoctorController::class, 'updateProfile']);
        Route::put('/doctors/me/schedule', [DoctorController::class, 'updateSchedule']);

        // Doctor clinic management - specific routes before wildcards
        Route::get('/doctors/search-clinics', [DoctorDashboardController::class, 'searchClinics']);
        Route::get('/doctors/clinic-invitations', [DoctorDashboardController::class, 'getClinicInvitations']);
        Route::post('/doctors/clinic-requests', [DoctorDashboardController::class, 'requestToJoinClinic']);
        Route::delete('/doctors/clinic-requests/{id}', [DoctorDashboardController::class, 'cancelClinicRequest']);
        Route::put('/doctors/clinic-invitations/{id}/respond', [DoctorDashboardController::class, 'respondToInvitation']);
        Route::post('/doctors/leave-clinic', [DoctorDashboardController::class, 'leaveClinic']);

        // Guest visits management
        Route::get('/doctors/my-guest-visits', [DoctorDashboardController::class, 'getMyGuestVisits']);
        Route::get('/doctors/guest-visits/{id}/services', [DoctorDashboardController::class, 'getGuestVisitServices']);
        Route::post('/doctors/guest-visits/{id}/services', [DoctorDashboardController::class, 'addGuestVisitService']);
        Route::put('/doctors/guest-visits/{gostovanjeId}/services/{uslugaId}', [DoctorDashboardController::class, 'updateGuestVisitService']);
        Route::delete('/doctors/guest-visits/{gostovanjeId}/services/{uslugaId}', [DoctorDashboardController::class, 'deleteGuestVisitService']);
        Route::put('/doctors/guest-visits/{id}/respond', [DoctorDashboardController::class, 'respondToGuestVisit']);
        Route::delete('/doctors/guest-visits/{id}', [DoctorDashboardController::class, 'cancelGuestVisit']);

        // Doctor appointments
        Route::get('/appointments/doctor', [AppointmentController::class, 'doctorAppointments']);
        Route::post('/appointments/doctor/manual', [AppointmentController::class, 'storeManual']);
        Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);

        // Services (Doctor)
        Route::get('/services/my', [ServiceController::class, 'myServices']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

        // Doctor Dashboard - Service Categories
        Route::prefix('doctor')->group(function () {
            Route::get('/profile', [DoctorDashboardController::class, 'getProfile']);

            // Calendar sync
            Route::get('/calendar-sync', [CalendarSyncController::class, 'getSettings']);
            Route::put('/calendar-sync', [CalendarSyncController::class, 'updateSettings']);
            Route::post('/calendar-sync/regenerate-token', [CalendarSyncController::class, 'regenerateToken']);
            Route::post('/calendar-sync/sync-now', [CalendarSyncController::class, 'syncNow']);

            Route::get('/kategorije', [DoctorDashboardController::class, 'getKategorije']);
            Route::post('/kategorije', [DoctorDashboardController::class, 'createKategorija']);
            Route::put('/kategorije/{id}', [DoctorDashboardController::class, 'updateKategorija']);
            Route::delete('/kategorije/{id}', [DoctorDashboardController::class, 'deleteKategorija']);
            Route::post('/kategorije/reorder', [DoctorDashboardController::class, 'reorderKategorije']);
            Route::get('/usluge', [DoctorDashboardController::class, 'getUsluge']);
            Route::post('/usluge', [DoctorDashboardController::class, 'createUsluga']);
            Route::put('/usluge/{id}', [DoctorDashboardController::class, 'updateUsluga']);
            Route::delete('/usluge/{id}', [DoctorDashboardController::class, 'deleteUsluga']);
            Route::post('/usluge/reorder', [DoctorDashboardController::class, 'reorderUsluge']);
        });

    });

    // Clinic Dashboard (clinic staff only)
    Route::middleware('role:clinic')->prefix('clinic')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'updateProfile']);
        Route::put('/change-password', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'changePassword']);
        Route::get('/appointments', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getAppointments']);
        Route::get('/doctors', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getDoctors']);
        Route::post('/doctors', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'addDoctor']);
        Route::put('/doctors/{id}', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'updateDoctor']);
        Route::put('/appointments/{id}/status', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'updateAppointmentStatus']);
        Route::get('/statistics', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getStatistics']);

        // Doctor invitations and requests
        Route::get('/invitations', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getInvitations']);
        Route::get('/doctor-requests', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getDoctorRequests']);
        Route::post('/invitations', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'inviteDoctor']);
        Route::delete('/invitations/{id}', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'cancelInvitation']);
        Route::put('/doctor-requests/{id}/respond', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'respondToDoctorRequest']);
        Route::delete('/doctors/{id}', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'removeDoctor']);

        // Guest doctors (gostujući doktori)
        Route::get('/guest-doctors', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getGuestDoctors']);
        Route::post('/guest-doctors', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'addGuestDoctor']);
        Route::put('/guest-doctors/{id}', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'updateGuestDoctor']);
        Route::delete('/guest-doctors/{id}', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'cancelGuestDoctor']);
        Route::get('/search-doctors', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'searchDoctors']);
        Route::get('/search-existing-doctors', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'searchExistingDoctors']); // Alias

        // Calendar and appointments
        Route::get('/calendar-data', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getCalendarData']);
        Route::get('/appointments-by-date', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'getAppointmentsByDate']);
        Route::post('/appointments/manual', [\App\Http\Controllers\Api\ClinicDashboardController::class, 'createManualAppointment']);
    });

    // Appointments
    Route::get('/appointments/my', [AppointmentController::class, 'myAppointments']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'cancel']);

    // Recenzije with rate limiting
    Route::get('/recenzije/my', [RecenzijaController::class, 'myRecenzije']);
    Route::get('/recenzije/eligible-termini', [RecenzijaController::class, 'getEligibleTermini']);
    Route::post('/recenzije', [RecenzijaController::class, 'store'])
        ->middleware('throttle:10,60'); // 10 reviews per 60 minutes
    Route::put('/recenzije/{id}', [RecenzijaController::class, 'update']);
    Route::delete('/recenzije/{id}', [RecenzijaController::class, 'destroy']);
    Route::post('/recenzije/{id}/odgovor', [RecenzijaController::class, 'addResponse'])
        ->middleware('throttle:20,60'); // 20 responses per 60 minutes
    Route::get('/recenzije/termin/{terminId}/can-review', [RecenzijaController::class, 'canReview']);

    // Upload
    Route::post('/upload/image', [UploadController::class, 'uploadImage']);
    Route::delete('/upload/image', [UploadController::class, 'deleteImage']);

    // Notifikacije
    Route::get('/notifikacije', [NotifikacijaController::class, 'getAll']);
    Route::get('/notifikacije/neprocitane', [NotifikacijaController::class, 'getNeprocitane']);
    Route::put('/notifikacije/{id}/procitaj', [NotifikacijaController::class, 'markAsRead']);
    Route::put('/notifikacije/procitaj-sve', [NotifikacijaController::class, 'markAllAsRead']);
    Route::put('/notifikacije/procitaj-po-tipu', [NotifikacijaController::class, 'markByTypeAsRead']);
    Route::delete('/notifikacije/{id}', [NotifikacijaController::class, 'delete']);

    // Pitanja - Doctor notifications and responses
    Route::get('/pitanja/notifikacije', [\App\Http\Controllers\Api\PitanjeController::class, 'notifikacije']);
    Route::put('/pitanja/notifikacije/{id}/procitaj', [\App\Http\Controllers\Api\PitanjeController::class, 'oznaciNotifikacijuKaoProcitanu']);

    // Laboratory reviews (protected) with rate limiting
    Route::post('/laboratorije/{laboratorijaId}/recenzije', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'store'])
        ->middleware('throttle:10,60'); // 10 reviews per 60 minutes
    Route::put('/laboratorije/{laboratorijaId}/recenzije/{recenzijaId}', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'update']);
    Route::delete('/laboratorije/{laboratorijaId}/recenzije/{recenzijaId}', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'destroy']);

    // Blog - Doctor routes
    Route::get('/blog/my-posts', [\App\Http\Controllers\Api\BlogController::class, 'myPosts']);
    Route::post('/blog/posts', [\App\Http\Controllers\Api\BlogController::class, 'storeDoctor']);
    Route::put('/blog/posts/{id}', [\App\Http\Controllers\Api\BlogController::class, 'updateDoctor']);
    Route::delete('/blog/posts/{id}', [\App\Http\Controllers\Api\BlogController::class, 'destroyDoctor']);
    Route::get('/blog/can-write', [\App\Http\Controllers\Api\BlogController::class, 'canDoctorsWrite']);
    // Backward-compatible alias used by some frontend versions
    Route::get('/blog/can-doctors-write', [\App\Http\Controllers\Api\BlogController::class, 'canDoctorsWrite']);

    // Laboratory Dashboard (laboratory manager only)
    Route::middleware('role:laboratory')->prefix('laboratorija')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updateProfile']);
        Route::put('/change-password', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'changePassword']);
        Route::post('/change-password', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'changePassword']);
        Route::get('/statistics', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getStatistics']);

        // Analyses management
        Route::get('/analize', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getAnalize']);
        Route::post('/analize', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'createAnaliza']);
        Route::put('/analize/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updateAnaliza']);
        Route::delete('/analize/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'deleteAnaliza']);
        Route::put('/analize/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderAnalize']);
        Route::post('/analize/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderAnalize']);

        // Packages management
        Route::get('/paketi', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getPaketi']);
        Route::post('/paketi', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'createPaket']);
        Route::put('/paketi/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updatePaket']);
        Route::delete('/paketi/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'deletePaket']);
        Route::put('/paketi/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderPaketi']);
        Route::post('/paketi/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderPaketi']);

        // Gallery management
        Route::post('/galerija/upload', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'uploadGalleryImage']);
        Route::delete('/galerija/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'deleteGalleryImage']);

        // Working hours
        Route::put('/radno-vrijeme', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updateRadnoVrijeme']);
    });

    // Laboratory Dashboard aliases (compatibility with existing frontend /laboratory/* paths)
    Route::middleware('role:laboratory')->prefix('laboratory')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updateProfile']);
        Route::put('/change-password', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'changePassword']);
        Route::post('/change-password', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'changePassword']);
        Route::get('/statistics', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getStatistics']);

        Route::get('/analize', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getAnalize']);
        Route::post('/analize', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'createAnaliza']);
        Route::put('/analize/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updateAnaliza']);
        Route::delete('/analize/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'deleteAnaliza']);
        Route::put('/analize/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderAnalize']);
        Route::post('/analize/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderAnalize']);

        Route::get('/paketi', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'getPaketi']);
        Route::post('/paketi', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'createPaket']);
        Route::put('/paketi/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updatePaket']);
        Route::delete('/paketi/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'deletePaket']);
        Route::put('/paketi/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderPaketi']);
        Route::post('/paketi/reorder', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'reorderPaketi']);

        Route::post('/galerija', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'uploadGalleryImage']);
        Route::post('/galerija/upload', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'uploadGalleryImage']);
        Route::delete('/galerija/{id}', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'deleteGalleryImage']);
        Route::put('/radno-vrijeme', [\App\Http\Controllers\Api\LaboratorijaDashboardController::class, 'updateRadnoVrijeme']);
    });

    // Spa Dashboard (spa manager only)
    Route::middleware('role:spa_manager|spa')->prefix('spa')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\SpaDashboardController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\SpaDashboardController::class, 'updateProfile']);
        Route::get('/statistics', [\App\Http\Controllers\Api\SpaDashboardController::class, 'statistics']);
        Route::post('/toggle-active', [\App\Http\Controllers\Api\SpaDashboardController::class, 'toggleActive']);
        Route::get('/upiti', [\App\Http\Controllers\Api\SpaDashboardController::class, 'upiti']);
        Route::put('/upiti/{id}/procitan', [\App\Http\Controllers\Api\SpaDashboardController::class, 'oznaciUpitProcitan']);
        Route::put('/upiti/{id}/odgovoren', [\App\Http\Controllers\Api\SpaDashboardController::class, 'oznaciUpitOdgovoren']);
        Route::put('/upiti/{id}/zatvori', [\App\Http\Controllers\Api\SpaDashboardController::class, 'zatvoriUpit']);
        Route::get('/recenzije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'recenzije']);

        // Packages management
        Route::get('/paketi', [\App\Http\Controllers\Api\SpaDashboardController::class, 'paketi']);
        Route::post('/paketi', [\App\Http\Controllers\Api\SpaDashboardController::class, 'createPaket']);
        Route::put('/paketi/{id}', [\App\Http\Controllers\Api\SpaDashboardController::class, 'updatePaket']);
        Route::delete('/paketi/{id}', [\App\Http\Controllers\Api\SpaDashboardController::class, 'deletePaket']);
        Route::post('/paketi/reorder', [\App\Http\Controllers\Api\SpaDashboardController::class, 'reorderPaketi']);

        // Custom therapies management
        Route::get('/custom-terapije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'customTerapije']);
        Route::post('/custom-terapije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'createCustomTerapija']);
        Route::put('/custom-terapije/{id}', [\App\Http\Controllers\Api\SpaDashboardController::class, 'updateCustomTerapija']);
        Route::delete('/custom-terapije/{id}', [\App\Http\Controllers\Api\SpaDashboardController::class, 'deleteCustomTerapija']);
        Route::post('/custom-terapije/reorder', [\App\Http\Controllers\Api\SpaDashboardController::class, 'reorderTerapije']);
        Route::post('/reorder-terapije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'reorderTerapije']); // Alias

        // Available options
        Route::get('/available-vrste', [\App\Http\Controllers\Api\SpaDashboardController::class, 'availableVrste']);
        Route::get('/available-indikacije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'availableIndikacije']);
        Route::get('/available-terapije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'availableTerapije']);
        Route::get('/vrste/available', [\App\Http\Controllers\Api\SpaDashboardController::class, 'availableVrste']); // Alias
        Route::get('/indikacije/available', [\App\Http\Controllers\Api\SpaDashboardController::class, 'availableIndikacije']); // Alias
        Route::get('/terapije/available', [\App\Http\Controllers\Api\SpaDashboardController::class, 'availableTerapije']); // Alias

        // Images management
        Route::post('/upload-featured', [\App\Http\Controllers\Api\SpaDashboardController::class, 'uploadFeaturedImage']);
        Route::post('/upload-gallery', [\App\Http\Controllers\Api\SpaDashboardController::class, 'uploadGalleryImage']);
        Route::delete('/gallery-image', [\App\Http\Controllers\Api\SpaDashboardController::class, 'deleteGalleryImage']);
        Route::post('/set-featured', [\App\Http\Controllers\Api\SpaDashboardController::class, 'setFeaturedImage']);
        Route::post('/featured-image', [\App\Http\Controllers\Api\SpaDashboardController::class, 'uploadFeaturedImage']); // Alias
        Route::post('/galerija', [\App\Http\Controllers\Api\SpaDashboardController::class, 'uploadGalleryImage']); // Alias
        Route::delete('/galerija', [\App\Http\Controllers\Api\SpaDashboardController::class, 'deleteGalleryImage']); // Alias
        Route::post('/reorder-paketi', [\App\Http\Controllers\Api\SpaDashboardController::class, 'reorderPaketi']); // Alias
    });

    // Spa Dashboard legacy aliases (/banja/*)
    Route::middleware('role:spa_manager|spa')->prefix('banja')->group(function () {
        Route::get('/moja', [\App\Http\Controllers\Api\SpaDashboardController::class, 'profile']);
        Route::put('/moja', [\App\Http\Controllers\Api\SpaDashboardController::class, 'updateProfile']);
        Route::get('/statistika', [\App\Http\Controllers\Api\SpaDashboardController::class, 'statistics']);
        Route::post('/toggle-active', [\App\Http\Controllers\Api\SpaDashboardController::class, 'toggleActive']);
        Route::get('/upiti', [\App\Http\Controllers\Api\SpaDashboardController::class, 'upiti']);
        Route::put('/upiti/{id}/procitan', [\App\Http\Controllers\Api\SpaDashboardController::class, 'oznaciUpitProcitan']);
        Route::put('/upiti/{id}/odgovoren', [\App\Http\Controllers\Api\SpaDashboardController::class, 'oznaciUpitOdgovoren']);
        Route::put('/upiti/{id}/zatvori', [\App\Http\Controllers\Api\SpaDashboardController::class, 'zatvoriUpit']);
        Route::get('/recenzije', [\App\Http\Controllers\Api\SpaDashboardController::class, 'recenzije']);
    });

    // Care Home Dashboard (care home manager only)
    Route::middleware('role:dom_manager|care_home|care_home_manager')->prefix('dom')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\DomDashboardController::class, 'mojDom']);
        Route::put('/profile', [\App\Http\Controllers\Api\DomDashboardController::class, 'azurirajDom']);
        Route::get('/statistics', [\App\Http\Controllers\Api\DomDashboardController::class, 'statistike']);
        Route::get('/activity', [\App\Http\Controllers\Api\DomDashboardController::class, 'aktivnost']);

        // Inquiries management
        Route::get('/upiti', [\App\Http\Controllers\Api\DomDashboardController::class, 'upiti']);
        Route::put('/upiti/{id}', [\App\Http\Controllers\Api\DomDashboardController::class, 'azurirajUpit']);

        // Reviews management
        Route::get('/recenzije', [\App\Http\Controllers\Api\DomDashboardController::class, 'recenzije']);

        // Images management
        Route::post('/upload-slike', [\App\Http\Controllers\Api\DomDashboardController::class, 'uploadSlike']);
    });

    // Care Home dashboard aliases (compatibility with existing frontend /dom-dashboard/* paths)
    Route::middleware('role:dom_manager|care_home|care_home_manager')->prefix('dom-dashboard')->group(function () {
        Route::get('/moj-dom', [\App\Http\Controllers\Api\DomDashboardController::class, 'mojDom']);
        Route::put('/moj-dom', [\App\Http\Controllers\Api\DomDashboardController::class, 'azurirajDom']);
        Route::get('/statistike', [\App\Http\Controllers\Api\DomDashboardController::class, 'statistike']);
        Route::get('/aktivnost', [\App\Http\Controllers\Api\DomDashboardController::class, 'aktivnost']);
        Route::get('/upiti', [\App\Http\Controllers\Api\DomDashboardController::class, 'upiti']);
        Route::put('/upiti/{id}', [\App\Http\Controllers\Api\DomDashboardController::class, 'azurirajUpit']);
        Route::patch('/upiti/{id}', [\App\Http\Controllers\Api\DomDashboardController::class, 'azurirajUpit']);
        Route::get('/recenzije', [\App\Http\Controllers\Api\DomDashboardController::class, 'recenzije']);
        Route::post('/upload-slike', [\App\Http\Controllers\Api\DomDashboardController::class, 'uploadSlike']);
    });

    // Admin routes (admin only)
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);

        Route::post('/doctors', [AdminController::class, 'createDoctor']);
        Route::put('/doctors/{id}', [AdminController::class, 'updateDoktor']);
        Route::delete('/doctors/{id}', [AdminController::class, 'deleteDoktor']);

        Route::post('/clinics', [AdminController::class, 'createClinic']);
        Route::put('/clinics/{id}', [AdminController::class, 'updateClinic']);
        Route::delete('/clinics/{id}', [AdminController::class, 'deleteClinic']);

        Route::get('/cities', [CityController::class, 'adminIndex']);
        Route::post('/cities', [AdminController::class, 'createCity']);
        Route::put('/cities/{id}', [AdminController::class, 'updateCity']);
        Route::delete('/cities/{id}', [AdminController::class, 'deleteCity']);

        Route::get('/specialties/{id}', [AdminController::class, 'getSpecialty']);
        Route::post('/specialties', [AdminController::class, 'createSpecialty']);
        Route::post('/specialties/{id}', [AdminController::class, 'updateSpecialty']); // For FormData with _method
        Route::put('/specialties/{id}', [AdminController::class, 'updateSpecialty']);
        Route::delete('/specialties/{id}', [AdminController::class, 'deleteSpecialty']);
        Route::post('/specialties/reorder', [AdminController::class, 'updateSpecialtySortOrder']);

        // Admin Settings
        Route::get('/settings/templates', [SettingsController::class, 'getTemplates']);
        Route::put('/settings/templates', [SettingsController::class, 'updateTemplates']);
        Route::get('/settings/doctor-card', [SettingsController::class, 'getDoctorCardSettings']);
        Route::put('/settings/doctor-card', [SettingsController::class, 'updateDoctorCardSettings']);
        Route::get('/settings/clinic-card', [SettingsController::class, 'getClinicCardSettings']);
        Route::put('/settings/clinic-card', [SettingsController::class, 'updateClinicCardSettings']);
        Route::put('/settings/homepage', [SettingsController::class, 'updateHomepageSettings']);
        Route::post('/settings/specialty-template', [SettingsController::class, 'updateSpecialtyTemplate']);
        Route::put('/settings/blog-typography', [SettingsController::class, 'updateBlogTypography']);
        Route::put('/settings/listing-template', [SettingsController::class, 'updateListingTemplate']);

        // Admin: Laboratory reviews moderation
        Route::get('/laboratorije/{laboratorijaId}/recenzije/admin', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'adminIndex']);
        Route::post('/laboratorije/{laboratorijaId}/recenzije/{recenzijaId}/approve', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'approve']);
        Route::post('/laboratorije/{laboratorijaId}/recenzije/{recenzijaId}/reject', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'reject']);
        Route::post('/laboratorije/{laboratorijaId}/recenzije/bulk-approve', [\App\Http\Controllers\Api\LaboratorijaRecenzijaController::class, 'bulkApprove']);

        // Registration Management
        Route::get('/registration-requests', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'index']);
        Route::get('/registration-requests/{id}', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'show']);
        Route::post('/registration-requests/{id}/approve', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'approve']);
        Route::post('/registration-requests/{id}/reject', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'reject']);
        Route::delete('/registration-requests/{id}', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'delete']);
        Route::get('/registration-settings', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'getSettings']);
        Route::put('/registration-settings', [\App\Http\Controllers\Api\AdminRegistrationController::class, 'updateSettings']);

        // Blog Management
        Route::get('/blog/posts', [\App\Http\Controllers\Api\BlogController::class, 'adminIndex']);
        Route::post('/blog/posts', [\App\Http\Controllers\Api\BlogController::class, 'adminStore']);
        Route::put('/blog/posts/{id}', [\App\Http\Controllers\Api\BlogController::class, 'adminUpdate']);
        Route::delete('/blog/posts/{id}', [\App\Http\Controllers\Api\BlogController::class, 'adminDestroy']);
        Route::post('/blog/categories', [\App\Http\Controllers\Api\BlogController::class, 'adminStoreCategory']);
        Route::put('/blog/categories/{id}', [\App\Http\Controllers\Api\BlogController::class, 'adminUpdateCategory']);
        Route::put('/blog/categories-order', [\App\Http\Controllers\Api\BlogController::class, 'adminUpdateCategoriesOrder']);
        Route::delete('/blog/categories/{id}', [\App\Http\Controllers\Api\BlogController::class, 'adminDestroyCategory']);
        Route::get('/blog/settings', [\App\Http\Controllers\Api\BlogController::class, 'getSettings']);
        Route::put('/blog/settings', [\App\Http\Controllers\Api\BlogController::class, 'updateSettings']);

        // MKB-10 Management
        Route::get('/mkb10/kategorije', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'indexKategorije']);
        Route::post('/mkb10/kategorije', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'storeKategorija']);
        Route::put('/mkb10/kategorije/{id}', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'updateKategorija']);
        Route::delete('/mkb10/kategorije/{id}', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'destroyKategorija']);
        Route::get('/mkb10/podkategorije', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'indexPodkategorije']);
        Route::post('/mkb10/podkategorije', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'storePodkategorija']);
        Route::put('/mkb10/podkategorije/{id}', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'updatePodkategorija']);
        Route::delete('/mkb10/podkategorije/{id}', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'destroyPodkategorija']);
        Route::get('/mkb10/dijagnoze', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'indexDijagnoze']);
        Route::post('/mkb10/dijagnoze', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'storeDijagnoza']);
        Route::put('/mkb10/dijagnoze/{id}', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'updateDijagnoza']);
        Route::delete('/mkb10/dijagnoze/{id}', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'destroyDijagnoza']);
        Route::get('/mkb10/settings', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'getSettings']);
        Route::put('/mkb10/settings', [\App\Http\Controllers\Api\AdminMkb10Controller::class, 'updateSettings']);

        // Legal Settings
        Route::get('/settings/legal', [SettingsController::class, 'getLegalSettings']);
        Route::put('/settings/legal', [SettingsController::class, 'updateLegalSettings']);
        Route::put('/settings/privacy-policy', [SettingsController::class, 'updatePrivacyPolicy']);
        Route::put('/settings/terms-of-service', [SettingsController::class, 'updateTermsOfService']);
        Route::put('/settings/cookie', [SettingsController::class, 'updateCookieSettings']);

        // Laboratory Management
        Route::get('/laboratorije', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'index']);
        Route::get('/laboratorije/{id}', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'show']);
        Route::post('/laboratorije', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'store']);
        Route::put('/laboratorije/{id}', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'update']);
        Route::delete('/laboratorije/{id}', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'destroy']);
        Route::post('/laboratorije/{id}/verify', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'verify']);
        Route::post('/laboratorije/{id}/toggle-active', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'toggleActive']);
        Route::get('/laboratorije/statistics/all', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'getStatistics']);

        // Laboratory Categories Management
        Route::get('/laboratorije/kategorije/all', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'getKategorije']);
        Route::post('/laboratorije/kategorije', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'storeKategorija']);
        Route::put('/laboratorije/kategorije/{id}', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'updateKategorija']);
        Route::delete('/laboratorije/kategorije/{id}', [\App\Http\Controllers\Api\AdminLaboratorijaController::class, 'destroyKategorija']);

        // Logo Settings (admin only)
        Route::get('/logo-settings', [LogoSettingsController::class, 'index']);
        Route::put('/logo-settings', [LogoSettingsController::class, 'update']);
    });
});



/*
|--------------------------------------------------------------------------
| Admin Routes - Protected by auth:sanctum and admin middleware
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Admin Profile Management
    Route::get('/profile', [AdminController::class, 'getProfile']);
    Route::put('/profile', [AdminController::class, 'updateProfile']);
    Route::put('/password', [AdminController::class, 'changePassword']);

    // Admin Doctor Management
    Route::get('/doctors', [\App\Http\Controllers\Api\AdminDoctorController::class, 'index']);
    Route::post('/doctors/{id}/verify', [\App\Http\Controllers\Api\AdminDoctorController::class, 'verify']);
    Route::post('/doctors/{id}/unverify', [\App\Http\Controllers\Api\AdminDoctorController::class, 'unverify']);
    Route::post('/doctors/{id}/activate', [\App\Http\Controllers\Api\AdminDoctorController::class, 'activate']);
    Route::post('/doctors/{id}/deactivate', [\App\Http\Controllers\Api\AdminDoctorController::class, 'deactivate']);
    Route::get('/doctors/statistics', [\App\Http\Controllers\Api\AdminDoctorController::class, 'statistics']);

    // Admin Clinic Management
    Route::get('/clinics', [\App\Http\Controllers\Api\AdminClinicController::class, 'index']);
    Route::post('/clinics/{id}/verify', [\App\Http\Controllers\Api\AdminClinicController::class, 'verify']);
    Route::post('/clinics/{id}/unverify', [\App\Http\Controllers\Api\AdminClinicController::class, 'unverify']);
    Route::post('/clinics/{id}/activate', [\App\Http\Controllers\Api\AdminClinicController::class, 'activate']);
    Route::post('/clinics/{id}/deactivate', [\App\Http\Controllers\Api\AdminClinicController::class, 'deactivate']);
    Route::get('/clinics/statistics', [\App\Http\Controllers\Api\AdminClinicController::class, 'statistics']);

    // Admin Entities Management
    Route::get('/laboratories', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'getLaboratories']);
    Route::put('/laboratories/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'updateLaboratory']);
    Route::delete('/laboratories/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'deleteLaboratory']);

    // Spa management (canonical /admin/spas)
    Route::get('/spas/statistika/dashboard', [\App\Http\Controllers\Api\AdminBanjaController::class, 'statistics']);
    Route::get('/spas/recenzije', [\App\Http\Controllers\Api\AdminBanjaController::class, 'recenzije']);
    Route::post('/spas/recenzije/{id}/odobri', [\App\Http\Controllers\Api\AdminBanjaController::class, 'odobriRecenziju']);
    Route::delete('/spas/recenzije/{id}', [\App\Http\Controllers\Api\AdminBanjaController::class, 'obrisiRecenziju']);
    Route::get('/spas/upiti', [\App\Http\Controllers\Api\AdminBanjaController::class, 'upiti']);
    Route::get('/spas/audit-log/{id}', [\App\Http\Controllers\Api\AdminBanjaController::class, 'auditLog']);
    Route::post('/spas/{id}/verify', [\App\Http\Controllers\Api\AdminBanjaController::class, 'verify']);
    Route::post('/spas/{id}/toggle-active', [\App\Http\Controllers\Api\AdminBanjaController::class, 'toggleStatus']);
    Route::post('/spas', [\App\Http\Controllers\Api\AdminBanjaController::class, 'store']);
    Route::get('/spas', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'getSpas']);
    Route::put('/spas/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'updateSpa']);
    Route::delete('/spas/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'deleteSpa']);

    // Legacy spa admin aliases (/admin/banje/*)
    Route::get('/banje/statistika/dashboard', [\App\Http\Controllers\Api\AdminBanjaController::class, 'statistics']);
    Route::get('/banje/recenzije', [\App\Http\Controllers\Api\AdminBanjaController::class, 'recenzije']);
    Route::post('/banje/recenzije/{id}/odobri', [\App\Http\Controllers\Api\AdminBanjaController::class, 'odobriRecenziju']);
    Route::delete('/banje/recenzije/{id}', [\App\Http\Controllers\Api\AdminBanjaController::class, 'obrisiRecenziju']);
    Route::get('/banje/upiti', [\App\Http\Controllers\Api\AdminBanjaController::class, 'upiti']);
    Route::get('/banje/audit-log/{id}', [\App\Http\Controllers\Api\AdminBanjaController::class, 'auditLog']);
    Route::post('/banje/{id}/verify', [\App\Http\Controllers\Api\AdminBanjaController::class, 'verify']);
    Route::post('/banje/{id}/toggle-active', [\App\Http\Controllers\Api\AdminBanjaController::class, 'toggleStatus']);
    Route::post('/banje', [\App\Http\Controllers\Api\AdminBanjaController::class, 'store']);
    Route::get('/banje', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'getSpas']);
    Route::put('/banje/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'updateSpa']);
    Route::delete('/banje/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'deleteSpa']);

    Route::get('/care-homes', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'getCareHomes']);
    Route::put('/care-homes/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'updateCareHome']);
    Route::delete('/care-homes/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'deleteCareHome']);

    Route::get('/pitanja', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'getQuestions']);
    Route::put('/pitanja/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'updateQuestion']);
    Route::delete('/pitanja/{id}', [\App\Http\Controllers\Api\AdminEntitiesController::class, 'deleteQuestion']);

    // Medical Calendar - Admin routes
    Route::get('/medical-calendar', [MedicalCalendarController::class, 'adminIndex']);
    Route::post('/medical-calendar', [MedicalCalendarController::class, 'store']);
    Route::post('/medical-calendar/import-xml', [MedicalCalendarController::class, 'importXml']);
    Route::get('/medical-calendar/export-xml', [MedicalCalendarController::class, 'exportXml']);
    Route::post('/medical-calendar/bulk-delete', [MedicalCalendarController::class, 'bulkDestroy']);
    Route::put('/medical-calendar/{id}', [MedicalCalendarController::class, 'update']);
    Route::delete('/medical-calendar/{id}', [MedicalCalendarController::class, 'destroy']);
});

// Medical Calendar - Public routes
Route::get('/medical-calendar', [MedicalCalendarController::class, 'index']);
Route::get('/medical-calendar/{id}', [MedicalCalendarController::class, 'show']);
Route::get('/medical-calendar/categories/list', [MedicalCalendarController::class, 'getCategories']);
