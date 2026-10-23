@extends('admin.layout.app')
@section('title', $company->exists ? 'Edit Company' : 'Create Company')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center gap-3 mb-4"><a href="{{ route('admin.super-admin.companies.index') }}" class="btn btn-icon btn-label-secondary"><i class="bx bx-arrow-back"></i></a><div><h1 class="h4 mb-1">{{ $company->exists ? 'Edit company' : 'Create a company' }}</h1><p class="text-body-secondary mb-0">{{ $company->exists ? 'Update organization and subscription details.' : 'Create the company workspace and its first Admin account.' }}</p></div></div>
    <div class="card"><div class="card-body p-4">
        <form method="POST" action="{{ $company->exists ? route('admin.super-admin.companies.update',$company) : route('admin.super-admin.companies.store') }}">
            @csrf @if($company->exists) @method('PUT') @endif
            <h2 class="h6 mb-3">Company profile</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="name">Company name</label><input id="name" name="name" value="{{ old('name',$company->name) }}" required maxlength="255" class="form-control @error('name') is-invalid @enderror" placeholder="Acme Inc.">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="email">Contact email</label><input id="email" type="email" name="email" value="{{ old('email',$company->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="contact@company.com">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="domain">Company domain</label><input id="domain" name="domain" value="{{ old('domain',$company->domain) }}" class="form-control @error('domain') is-invalid @enderror" placeholder="company.com">@error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="subscription_plan">Subscription plan</label><select id="subscription_plan" name="subscription_plan" class="form-select @error('subscription_plan') is-invalid @enderror">@foreach(['starter'=>'Starter','growth'=>'Growth','business'=>'Business','enterprise'=>'Enterprise'] as $value=>$label)<option value="{{ $value }}" @selected(old('subscription_plan',$company->subscription_plan ?: 'starter')===$value)>{{ $label }}</option>@endforeach</select>@error('subscription_plan')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @if($company->exists)
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active',$company->is_active))><label class="form-check-label" for="is_active">Company subscription active</label></div></div>
                @else
                    <div class="col-12"><hr><h2 class="h6 mb-1">Company Admin</h2><p class="text-body-secondary small">This account will manage the company workspace and invite its team.</p></div>
                    <div class="col-md-4"><label class="form-label" for="admin_name">Admin name</label><input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required class="form-control @error('admin_name') is-invalid @enderror">@error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="admin_email">Admin email</label><input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required class="form-control @error('admin_email') is-invalid @enderror">@error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label" for="admin_password">Temporary password</label><input id="admin_password" name="admin_password" type="password" minlength="8" required class="form-control @error('admin_password') is-invalid @enderror">@error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @endif
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('admin.super-admin.companies.index') }}" class="btn btn-label-secondary">Cancel</a><button class="btn btn-primary" type="submit"><i class="bx bx-check me-1"></i>{{ $company->exists ? 'Save changes' : 'Create company' }}</button></div>
        </form>
    </div></div>
</div>
@endsection
