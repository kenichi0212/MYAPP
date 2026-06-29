@php
    /** @var \App\Models\User|null $user */
    $user ??= null;
@endphp

<div>
    <x-input-label for="name" value="氏名" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div class="mt-4">
    <x-input-label for="email" value="メールアドレス" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div class="mt-4">
    <x-input-label for="password" :value="$user ? 'パスワード（変更する場合のみ入力）' : 'パスワード'" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $user" />
    <x-input-error class="mt-2" :messages="$errors->get('password')" />
</div>

<div class="mt-4">
    <x-input-label for="password_confirmation" value="パスワード（確認）" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $user" />
</div>

<div class="mt-4">
    <x-input-label for="role" value="役割" />
    <select id="role" name="role" class="mt-1 block w-full border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm min-h-[44px]" required>
        @foreach (\App\Enums\UserRole::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value) === $role->value)>
                {{ $role->label() }}
            </option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('role')" />
</div>

<div class="mt-4">
    <x-input-label for="store_id" value="担当店舗（店舗担当者の場合は必須）" />
    <select id="store_id" name="store_id" class="mt-1 block w-full border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm min-h-[44px]">
        <option value="">未選択</option>
        @foreach ($stores as $store)
            <option value="{{ $store->id }}" @selected((int) old('store_id', $user?->store_id) === $store->id)>
                {{ $store->store_name }}
            </option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('store_id')" />
</div>
