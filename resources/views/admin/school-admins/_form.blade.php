@php($editing = isset($admin))

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">School *</label>
    <select name="school_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">Select School</option>
        @foreach($schools as $school)
            <option value="{{ $school->id }}" {{ old('school_id', $admin->school_id ?? '') == $school->id ? 'selected' : '' }}>
                {{ $school->name }}
            </option>
        @endforeach
    </select>
    @error('school_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
    <input type="text" name="name" value="{{ old('name', $admin->name ?? '') }}" required
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
    <input type="email" name="email" value="{{ old('email', $admin->email ?? '') }}" required
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
    <input type="text" name="phone" value="{{ old('phone', $admin->phone ?? '') }}"
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $editing ? 'New Password' : 'Password *' }}</label>
    <input type="password" name="password" {{ $editing ? '' : 'required' }}
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    @if($editing) <p class="text-gray-500 text-xs mt-1">Leave blank to keep the current password.</p> @endif
    @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
    <input type="password" name="password_confirmation" {{ $editing ? '' : 'required' }}
           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
</div>