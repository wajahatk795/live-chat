@extends('admin.layout.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- ================= BASIC INFO ================= -->
            <div class="col-12 col-xl-6 d-flex">
                <div class="card rounded-4 border-0 w-100">
                    <div class="card-body">

                        <h5 class="mb-4 fw-semibold">
                            <i class="fa-solid fa-user me-2"></i>
                            Profile Information
                        </h5>
                        @php
                            $user = DB::table('users')
                                ->where('id', auth()->user()->id)
                                ->first();
                        @endphp
                        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Image -->
                            <div class="mb-3 text-center">
                                <label class="form-label d-block">Profile Image</label>
                                <div class="position-relative d-inline-block">
                                    <img src="{{ asset($user->image ?? 'assets/images/default.png') }}"
                                        alt="Profile Image" class="rounded-circle border"
                                        style="width:120px; height:120px; object-fit:cover; cursor:pointer;">

                                    <input type="file" name="image" id="profile-image" class="form-control d-none"
                                        accept="image/*">
                                    <span class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2"
                                        style="cursor:pointer;" onclick="document.getElementById('profile-image').click();">
                                        <i class="fa-solid fa-camera"></i>
                                    </span>
                                </div>
                                @error('image')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Name -->
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name', auth()->user()->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email (Read Only) -->
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>

                            <!-- Email (Read Only) -->
                            @if (Auth::user()->role == '1')
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="Role" class="form-control" value="Admin" readonly>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>
                            @elseif(Auth::user()->role == '2')
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="Role" class="form-control" value="Client" readonly>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>
                            @elseif(Auth::user()->role == '3')
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="Role" class="form-control" value="Agent" readonly>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>
                            @endif
                            @if(Auth::user()->isSuperAdmin())
                                <div class="mb-3"><label class="form-label">Role</label><input type="text" class="form-control" value="Super Admin" readonly><small class="text-muted">Role cannot be changed</small></div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                    name="phone" value="{{ old('phone', auth()->user()->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror"
                                    name="address" value="{{ old('address', auth()->user()->address) }}" required>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">About</label>
                                <textarea class="form-control" id="about" name="about" rows="4"
                                    placeholder="Write something about the user...">{{ $user->about }}</textarea>
                                @error('about')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="btn btn-primary w-100">
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ================= PASSWORD UPDATE ================= -->
            <div class="col-12 col-xl-6 d-flex">
                <div class="card rounded-4 border-0 w-100">
                    <div class="card-body">

                        <h5 class="mb-4 fw-semibold">
                            <i class="fa-solid fa-lock me-2"></i>
                            Change Password
                        </h5>

                        <form method="POST" action="{{ route('profile.password.update') }}">
                            @csrf

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>

                            <button class="btn btn-warning w-100">
                                Update Password
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            <!-- ================= ACCOUNT INFO ================= -->
            <div class="col-12 mt-4">
                <div class="card rounded-4 border-0">
                    <div class="card-body">

                        <h5 class="mb-3 fw-semibold">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            Account Information
                        </h5>

                        <div class="row">
                            @if (Auth::user()->role == '1')
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Role</p>
                                    <h6>Admin</h6>
                                </div>
                            @elseif(Auth::user()->role == '2')
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Role</p>
                                    <h6>Client</h6>
                                </div>
                            @elseif(Auth::user()->role == '3')
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Role</p>
                                    <h6>Agent</h6>
                                </div>
                            @endif
                            @if(Auth::user()->isSuperAdmin())
                                <div class="col-md-4"><p class="mb-1 text-muted">Role</p><h6>Super Admin</h6></div>
                            @endif

                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Account Created</p>
                                <h6>{{ auth()->user()->created_at?->format('d M Y') }}</h6>
                            </div>

                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Status</p>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection

<script>
    const profileImage = document.getElementById('profile-image');
    const preview = document.getElementById('profile-preview');

    profileImage.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>
