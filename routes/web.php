<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatShortcutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\Admin\FormSubmissionsController;


Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/signin', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('/signup', [RegisterController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:1', 'lastseen'])->prefix('admin')->group(function () {

    Route::get('users', [UserController::class, 'index'])->name('admin.users');
    Route::get('users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('users/store', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('users/{id}/update', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('users/{id}/destroy', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('users/data', [UserController::class, 'getUsers'])->name('admin.users.data');


    Route::get('brand/data', [BrandController::class, 'getdata'])->name('admin.brand.getdata');
    Route::post('brand/status/update', [BrandController::class, 'changeStatus'])->name('admin.brand.status.update');
    Route::get('brand', [BrandController::class, 'index'])->name('admin.brand');
    Route::get('brand/create', [BrandController::class, 'create'])->name('admin.brand.create');
    Route::post('brand/store', [BrandController::class, 'store'])->name('admin.brand.store');
    Route::get('brand/edit/{brand}', [BrandController::class, 'edit'])->name('admin.brand.edit');
    Route::put('brand/update/{brand}', [BrandController::class, 'update'])->name('admin.brand.update');
    Route::delete('brand/destroy/{brand}', [BrandController::class, 'destroy'])->name('admin.brand.destroy');
    Route::post('brand/chat-settings/store', [BrandController::class, 'chatSettingsStore'])->name('admin.brand.chat.settings');
    Route::post('brand/{id}/resync-ai', [BrandController::class, 'resyncAi'])->name('admin.brand.resync-ai');
    Route::get('brand/{brand}/install', [BrandController::class, 'install'])->name('admin.brand.install');

    // Route::get('website/data', [WebsiteController::class, 'getdata'])->name('admin.website.getdata');
    // Route::get('website', [WebsiteController::class, 'index'])->name('admin.website');
    // Route::get('website/create', [WebsiteController::class, 'create'])->name('admin.website.create');
    // Route::post('website/store', [WebsiteController::class, 'store'])->name('admin.website.store');
    // Route::get('website/edit/{id}', [WebsiteController::class, 'edit'])->name('admin.website.edit');
    // Route::put('website/update/{id}', [WebsiteController::class, 'update'])->name('admin.website.update');
    // Route::delete('website/destroy/{id}', [WebsiteController::class, 'destroy'])->name('admin.website.destroy');
});

Route::middleware(['auth', 'role:1,2', 'lastseen'])->prefix('admin')->group(function () {

    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('visitor-analytics', [AdminController::class, 'visitorAnalytics'])->name('admin.visitor-analytics');

    Route::get('chat', [AdminController::class, 'chat'])->name('admin.chat');
    Route::get('chat-contacts', [AdminController::class, 'chatContacts'])->name('admin.chat.contacts');
    Route::get('chat/{chatId}', [AdminController::class, 'show'])->name('admin.chat.show');
    Route::get('chat/{chatId}/profile', [AdminController::class, 'chatProfile'])->name('admin.chat.profile');
    Route::patch('chat/{chatId}/profile', [AdminController::class, 'updateChatProfile'])->name('admin.chat.profile.update');
    Route::get('chat/{chatId}/messages', [AdminController::class, 'messages'])->name('admin.chat.messages');
    Route::get('chat/{chatId}/shortcuts', [ChatShortcutController::class, 'index'])->name('admin.chat.shortcuts');
    Route::post('chat/{chatId}/shortcuts', [ChatShortcutController::class, 'store'])->name('admin.chat.shortcuts.store');
    Route::put('chat/{chatId}/shortcuts/{shortcutId}', [ChatShortcutController::class, 'update'])->name('admin.chat.shortcuts.update');
    Route::delete('chat-shortcuts/{shortcutId}', [ChatShortcutController::class, 'destroy'])->name('admin.chat.shortcuts.destroy');
    Route::get('chat-shortcuts', [ChatShortcutController::class, 'manage'])->name('admin.chat-shortcuts.index');
    Route::post('chat-shortcuts', [ChatShortcutController::class, 'manageStore'])->name('admin.chat-shortcuts.store');
    Route::put('chat-shortcuts/{shortcutId}', [ChatShortcutController::class, 'manageUpdate'])->name('admin.chat-shortcuts.update');

    Route::post('send-message', [MessageController::class, 'send'])->name('admin.chat.send');
    Route::post('chat/start', [MessageController::class, 'chartStart'])->name('admin.chat.start');
    Route::post('chat/typing', [MessageController::class, 'typing'])->name('admin.chat.typing');
    Route::post('chat/mark-read', [MessageController::class, 'markRead'])->name('admin.chat.markRead');
    Route::post('chat/close', [MessageController::class, 'closeChat'])->name('admin.chat.close');

    // Notifications
    Route::get('notification', [AdminController::class, 'notification'])->name('admin.notification');
    Route::get('notification/data', [AdminController::class, 'getNotification'])->name('admin.notification.data');
    Route::post('notifications/mark-as-read', [AdminController::class, 'markAsRead'])->name('admin.notifications.markAsRead');

    Route::get('visitor/data', [VisitorController::class, 'getdata'])->name('admin.visitor.getdata');
    Route::get('visitor', [VisitorController::class, 'index'])->name('admin.visitor');
    // Route::get('visitor/create', [VisitorController::class, 'create'])->name('admin.visitor.create');
    // Route::post('visitor/store', [VisitorController::class, 'store'])->name('admin.visitor.store');
    Route::get('visitor/edit/{id}', [VisitorController::class, 'edit'])->name('admin.visitor.edit');
    Route::put('visitor/update/{id}', [VisitorController::class, 'update'])->name('admin.visitor.update');
    Route::delete('visitor/destroy/{id}', [VisitorController::class, 'destroy'])->name('admin.visitor.destroy');
    Route::delete('visitor/{id}', [VisitorController::class, 'destroy'])->name('admin.visitor.destroy');
    Route::get('visitor/edit-user/{id}', [VisitorController::class, 'editUser'])->name('admin.visitor.edit-user');
});

Route::middleware(['auth', 'lastseen'])->group(function () {

    Route::get('/admin/profile', [ProfileController::class, 'profile'])->name('admin.profile.index');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// Form Submissions - server‑side DataTables endpoint and page view
Route::middleware(['auth', 'lastseen'])->prefix('admin')->group(function () {
    Route::get('form-submissions', [FormSubmissionsController::class, 'index'])->name('admin.form-submissions');
    Route::get('form-submissions/data', [FormSubmissionsController::class, 'getFormSubmissions'])->name('admin.form-submissions.data');
    Route::get('form-submissions/{id}', [FormSubmissionsController::class, 'show'])->name('admin.form-submissions.show');
});


Route::get('/widget.js', function () {
    return response()->file(public_path('widget/widget.js'), [
        'Content-Type' => 'application/javascript'
    ]);
});

Route::post('/notifications/mark-read', function () {
    $user = auth()->user();
    $user->unreadNotifications->markAsRead();

    return response()->json(['status' => 'success']);
})->name('notifications.markRead');