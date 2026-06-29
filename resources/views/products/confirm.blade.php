<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            商品情報確認
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-600">JANコード：{{ $jan_code }}</p>

                    @php
                        $sourceLabel = match ($name_source) {
                            'master' => '自社マスタ',
                            'api' => '外部API取得',
                            default => '手入力',
                        };
                        $sourceClass = match ($name_source) {
                            'master' => 'bg-success/10 text-success border-success',
                            'api' => 'bg-warning/10 text-warning border-warning',
                            default => 'bg-gray-100 text-gray-600 border-gray-300',
                        };
                    @endphp
                    <span class="text-xs font-semibold px-2 py-1 rounded-full border {{ $sourceClass }}">
                        取得元：{{ $sourceLabel }}
                    </span>
                </div>

                @if (! $found)
                    <p class="text-sm text-danger mb-4">
                        自社マスタ・外部APIのいずれにも該当する商品情報が見つかりませんでした。手入力してください。
                    </p>
                @endif

                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-500">商品名</dt>
                        <dd class="text-base text-gray-900">{{ $product_name ?? '（未取得）' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">メーカー名</dt>
                        <dd class="text-base text-gray-900">{{ $maker_name ?? '（未取得）' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('barcode-scan.create') }}" class="text-sm text-gray-600 underline">
                        バーコード読取に戻る
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
