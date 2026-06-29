<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CSVインポート　取込内容の確認
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600">ファイル名：{{ $fileName }}</p>

                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div class="p-4 bg-gray-50 rounded-md">
                        <div class="text-2xl font-semibold">{{ $totalRows }}</div>
                        <div class="text-sm text-gray-500">対象行数</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-md">
                        <div class="text-2xl font-semibold text-success">{{ $successCount }}</div>
                        <div class="text-sm text-gray-500">成功件数</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-md">
                        <div class="text-2xl font-semibold text-danger">{{ $errorCount }}</div>
                        <div class="text-sm text-gray-500">エラー件数</div>
                    </div>
                </div>

                @if ($errorCount > 0)
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-800 mb-2">エラー内容</h3>
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500">
                                    <th class="py-2">行番号</th>
                                    <th class="py-2">エラー内容</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($errors as $error)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2">{{ $error['row_number'] }}</td>
                                        <td class="py-2 text-danger">{{ $error['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 mt-6">
                    <a href="{{ route('csv-imports.create') }}" class="text-sm text-gray-600 underline">やり直す</a>
                    <form method="POST" action="{{ route('csv-imports.confirm') }}">
                        @csrf
                        <x-primary-button>この内容で取込を確定する</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
