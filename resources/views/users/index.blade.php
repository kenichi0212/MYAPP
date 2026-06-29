<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザー管理
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 text-sm font-medium text-success">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="flex justify-end mb-4">
                    <a href="{{ route('users.create') }}" class="inline-flex items-center min-h-[44px] px-4 py-2 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary/90">
                        新規ユーザー作成
                    </a>
                </div>

                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500">
                            <th class="py-2">氏名</th>
                            <th class="py-2">メールアドレス</th>
                            <th class="py-2">役割</th>
                            <th class="py-2">担当店舗</th>
                            <th class="py-2">状態</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $user->name }}</td>
                                <td class="py-2">{{ $user->email }}</td>
                                <td class="py-2">{{ $user->role->label() }}</td>
                                <td class="py-2">{{ $user->store?->store_name ?? '-' }}</td>
                                <td class="py-2">
                                    @if ($user->is_active)
                                        <span class="text-success">有効</span>
                                    @else
                                        <span class="text-danger">無効</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right space-x-2">
                                    <a href="{{ route('users.edit', $user) }}" class="text-primary underline">編集</a>
                                    <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm text-gray-600 underline">
                                            {{ $user->is_active ? '無効化' : '有効化' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
