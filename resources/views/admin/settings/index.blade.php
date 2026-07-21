@extends('layouts.ocrs')
@section('title', 'System Settings')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    <aside class="sidebar">
        <nav>
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.applications.index') }}">Applications</a>
            <a href="{{ route('admin.intakes.index') }}">Intakes</a>
            <a href="{{ route('admin.fees.index') }}">Fees</a>
            <a href="{{ route('admin.documents.index') }}">Documents</a>
            <a href="{{ route('admin.settings.index') }}" class="active">Settings</a>
        </nav>
    </aside>
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">System Configuration</h1>
        
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')
            
            @foreach($settings as $group => $groupSettings)
                <div class="card mt-4">
                    <h3>{{ ucfirst($group) }} Settings</h3>
                    <div class="grid-2 mt-4">
                        @foreach($groupSettings as $setting)
                            <div class="form-group">
                                <label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                                @if($setting->type === 'boolean')
                                    <select name="{{ $setting->key }}">
                                        <option value="1" @selected($setting->value == '1')>Enabled</option>
                                        <option value="0" @selected($setting->value == '0')>Disabled</option>
                                    </select>
                                @else
                                    <input type="text" name="{{ $setting->key }}" value="{{ $setting->value }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                <button type="submit" class="btn btn-accent">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
