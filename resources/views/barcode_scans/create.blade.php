<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            バーコード読取・登録
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- 店舗選択（インクリメンタルサーチ） --}}
                <div class="mb-6">
                    <label for="store-name-input" class="block text-sm text-gray-600 mb-1">
                        店舗<span class="text-danger">　*</span>
                    </label>
                    <input
                        type="text"
                        id="store-name-input"
                        list="store-datalist"
                        autocomplete="off"
                        class="w-full rounded-md border-gray-300 shadow-sm min-h-[44px]"
                        placeholder="店舗名を入力して絞り込む"
                        @if(count($stores) === 1) value="{{ $stores->first()->store_name }}" readonly @endif
                    >
                    <datalist id="store-datalist">
                        @foreach ($stores as $store)
                            <option value="{{ $store->store_name }}">{{ $store->store_code }}</option>
                        @endforeach
                    </datalist>
                    <input type="hidden" id="store-id-select">
                    <p id="store-name-error" class="mt-1 text-sm text-danger hidden">
                        一覧にある店舗名を選択してください。
                    </p>
                </div>

                <script>
                window.__storeMap = @json($stores->pluck('id', 'store_name'));
                @if(count($stores) === 1)
                document.getElementById('store-id-select').value = '{{ $stores->first()->id }}';
                @endif
                </script>

                {{-- スキャナーセクション --}}
                <div id="scanner-section">
                    <p class="text-sm text-gray-600 mb-4">
                        商品のバーコード（JANコード）をカメラに映してください。カメラへのアクセス許可が必要です。
                    </p>

                    <div class="relative bg-black rounded-md overflow-hidden aspect-video">
                        <video id="scanner-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                    </div>

                    <p id="scanner-status" class="mt-3 text-sm text-gray-500">カメラを起動しています…</p>

                    {{-- 手入力 --}}
                    <div class="mt-4 text-center">
                        <button type="button" id="manual-input-toggle" class="text-sm text-primary underline">
                            うまく読み取れない場合はJANコードを手入力する
                        </button>
                    </div>

                    <div id="manual-input-form" class="mt-4 p-4 bg-gray-50 rounded-md hidden">
                        <label for="manual-jan-code" class="block text-sm text-gray-600 mb-1">JANコード（8桁または13桁の数字）</label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="manual-jan-code"
                                inputmode="numeric"
                                maxlength="13"
                                class="flex-1 rounded-md border-gray-300 shadow-sm"
                                placeholder="例：4901234567894"
                            >
                            <button type="button" id="manual-jan-submit" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none transition ease-in-out duration-150">
                                確定
                            </button>
                        </div>
                        <p id="manual-jan-error" class="mt-2 text-sm text-danger hidden">
                            JANコードの形式が正しくありません（8桁または13桁の数字）
                        </p>
                    </div>
                </div>

                {{-- 商品情報フォームセクション（スキャン後に表示） --}}
                <div id="product-form-section" class="hidden">

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-500">JANコード</p>
                            <p id="product-jan-display" class="text-base font-semibold text-gray-800"></p>
                        </div>
                        <span id="name-source-badge" class="text-xs font-semibold px-2 py-1 rounded-full border"></span>
                    </div>

                    <p id="product-not-found-msg" class="hidden text-sm text-danger mb-4">
                        自社マスタ・外部APIのいずれにも該当する商品情報が見つかりませんでした。商品名・メーカー名を手入力してください。
                    </p>

                    <p class="text-sm text-gray-500 mb-4">
                        取得した内容は初期値です。必要に応じて修正してから登録してください。
                    </p>

                    <div id="submit-feedback" class="hidden mb-4 p-3 rounded-md text-sm"></div>

                    <form id="product-confirm-form" class="space-y-4"
                          data-submit-url="{{ route('api.check-logs.store') }}"
                          data-lookup-url="{{ route('api.products.lookup') }}">
                        <input type="hidden" id="form-jan-code" name="jan_code">
                        <input type="hidden" id="form-name-source" name="name_source">

                        <div>
                            <label for="product-name-input" class="block text-sm text-gray-600 mb-1">
                                商品名<span class="text-danger">　*</span>
                            </label>
                            <input
                                type="text"
                                id="product-name-input"
                                name="product_name"
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
                                class="w-full sm:w-32 rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <x-primary-button type="submit" id="product-confirm-submit">登録する</x-primary-button>
                            <button type="button" id="back-to-scan-btn" class="text-sm text-gray-600 underline">
                                再スキャンに戻る
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-6">
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 underline">ホームへ戻る</a>
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
        @vite('resources/js/barcode-scan.js')
    @endpush
</x-app-layout>
