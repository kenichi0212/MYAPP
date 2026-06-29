<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            バーコード読取
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    商品のバーコード（JANコード）をカメラに映してください。カメラへのアクセス許可が必要です。
                </p>

                <div class="relative bg-black rounded-md overflow-hidden aspect-video">
                    <video id="scanner-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                </div>

                <p id="scanner-status" class="mt-3 text-sm text-gray-500">カメラを起動しています…</p>

                <div data-scanner-result-wrapper class="mt-4 p-4 bg-success/10 border border-success rounded-md hidden">
                    <p class="text-sm text-gray-600">読み取ったJANコード</p>
                    <p id="scanner-result" class="text-lg font-semibold text-gray-900"></p>
                    <input type="hidden" id="scanner-result-input" name="jan_code">
                    <button type="button" id="scanner-retry" class="mt-3 text-sm text-primary underline">
                        再読取する
                    </button>
                </div>

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
                        <x-primary-button type="button" id="manual-jan-submit">確定</x-primary-button>
                    </div>
                    <p id="manual-jan-error" class="mt-2 text-sm text-danger hidden">
                        JANコードの形式が正しくありません（8桁または13桁の数字）
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 underline">ホームへ戻る</a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/barcode-scan.js')
    @endpush
</x-app-layout>
