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
                        自社マスタ・外部APIのいずれにも該当する商品情報が見つかりませんでした。商品名・メーカー名を手入力してください。
                    </p>
                @endif

                <p class="text-sm text-gray-500 mb-4">
                    取得した内容は初期値です。必要に応じて修正してから次の入力に進んでください。
                </p>

                <div id="submit-feedback" class="hidden mb-4 p-3 rounded-md text-sm"></div>

                <form id="product-confirm-form" class="space-y-4"
      data-submit-url="{{ route('api.check-logs.store') }}"
      data-redirect-url="{{ route('barcode-scan.create') }}">
                    <input type="hidden" name="jan_code" value="{{ $jan_code }}">
                    <input type="hidden" name="name_source" value="{{ $name_source }}">

                    <div>
                        <label for="store-id-input" class="block text-sm text-gray-600 mb-1">
                            店舗<span class="text-danger">　*</span>
                        </label>
                        <select id="store-id-input" name="store_id" required class="w-full rounded-md border-gray-300 shadow-sm">
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="product-name-input" class="block text-sm text-gray-600 mb-1">
                            商品名<span class="text-danger">　*</span>
                        </label>
                        <input
                            type="text"
                            id="product-name-input"
                            name="product_name"
                            value="{{ $product_name }}"
                            required
                            class="w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="商品名を入力してください"
                        >
                    </div>

                    <div>
                        <label for="maker-name-input" class="block text-sm text-gray-600 mb-1">メーカー名</label>
                        <input
                            type="text"
                            id="maker-name-input"
                            name="maker_name"
                            value="{{ $maker_name }}"
                            class="w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="メーカー名を入力してください（任意）"
                        >
                    </div>

                    <div>
                        <label for="expiry-date-input" class="block text-sm text-gray-600 mb-1">
                            賞味期限<span class="text-danger">　*</span>
                        </label>
                        <input
                            type="date"
                            id="expiry-date-input"
                            name="expiry_date"
                            min="{{ now()->toDateString() }}"
                            required
                            class="w-full rounded-md border-gray-300 shadow-sm"
                        >
                        <p id="expiry-date-error" class="mt-1 text-sm text-danger hidden">
                            賞味期限に過去の日付は登録できません。
                        </p>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" id="is-zero-report-input" name="is_zero_report" value="1" class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">売場に商品が無い（数量0として登録する）</span>
                        </label>
                    </div>

                    <div>
                        <label for="quantity-input" class="block text-sm text-gray-600 mb-1">
                            数量（バラ数）<span class="text-danger">　*</span>
                        </label>
                        <input
                            type="number"
                            id="quantity-input"
                            name="quantity"
                            min="0"
                            step="1"
                            required
                            class="w-32 rounded-md border-gray-300 shadow-sm"
                        >
                    </div>

                    <div>
                        <x-primary-button type="submit" id="product-confirm-submit">登録する</x-primary-button>
                    </div>
                </form>

                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('barcode-scan.create') }}" class="text-sm text-gray-600 underline">
                        バーコード読取に戻る
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 重複ロット確認ダイアログ --}}
    <div id="duplicate-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         aria-modal="true" role="dialog" aria-labelledby="duplicate-dialog-title">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
            <h3 id="duplicate-dialog-title" class="text-base font-semibold text-gray-800 mb-2">重複登録の確認</h3>
            <p class="text-sm text-gray-600 mb-1">
                同一ロット（商品・店舗・賞味期限）の登録が既に存在します。
            </p>
            <p class="text-sm text-gray-600 mb-4">
                現在の登録数量：<span id="existing-quantity-display" class="font-semibold text-gray-800"></span>
            </p>
            <div class="flex flex-col gap-2">
                <button id="duplicate-add-btn" type="button"
                    class="w-full px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:opacity-90">
                    数量を加算する
                </button>
                <button id="duplicate-separate-btn" type="button"
                    class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-300">
                    別履歴として登録する
                </button>
                <button id="duplicate-cancel-btn" type="button"
                    class="text-sm text-gray-500 underline mt-1">
                    キャンセル
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/product-confirm.js')
    @endpush
</x-app-layout>
