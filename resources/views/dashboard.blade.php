<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ホーム
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- サマリカード --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- 要確認件数 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 {{ $needsAttentionCount > 0 ? 'border-red-500' : 'border-green-500' }}">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">要確認ロット数</p>
                        <p class="mt-1 text-4xl font-bold {{ $needsAttentionCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $needsAttentionCount }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">賞味期限3ヶ月以内かつ今月未チェック</p>
                        @if ($needsAttentionCount > 0)
                            <a href="{{ route('check-logs.index', ['needs_attention_only' => 1]) }}"
                               class="mt-3 inline-block text-sm text-red-600 hover:underline">
                                一覧で確認 →
                            </a>
                        @endif
                    </div>
                </div>

                {{-- 1ヶ月以内に期限が切れるロット数 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 {{ $expiringWithin1MonthCount > 0 ? 'border-yellow-500' : 'border-green-500' }}">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">1ヶ月以内に期限切れ</p>
                        <p class="mt-1 text-4xl font-bold {{ $expiringWithin1MonthCount > 0 ? 'text-yellow-600' : 'text-green-600' }}">
                            {{ $expiringWithin1MonthCount }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">現在登録中ロットのうち期限が1ヶ月未満のもの</p>
                        @if ($expiringWithin1MonthCount > 0)
                            <a href="{{ route('check-logs.index', ['expiry_within' => 1]) }}"
                               class="mt-3 inline-block text-sm text-yellow-600 hover:underline">
                                一覧で確認 →
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- メニューリンク --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">メニュー</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <a href="{{ route('barcode-scan.create') }}"
                           class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-indigo-50 hover:border-indigo-300 transition">
                            <span class="text-2xl">📷</span>
                            <div>
                                <p class="font-medium text-gray-800">バーコード読取</p>
                                <p class="text-xs text-gray-500">商品をスキャンして登録</p>
                            </div>
                        </a>

                        <a href="{{ route('check-logs.index') }}"
                           class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-indigo-50 hover:border-indigo-300 transition">
                            <span class="text-2xl">📋</span>
                            <div>
                                <p class="font-medium text-gray-800">商品一覧</p>
                                <p class="text-xs text-gray-500">賞味期限チェック履歴を確認</p>
                            </div>
                        </a>

                        @if (auth()->user()->role->canManageAllStores())
                        <a href="{{ route('csv-imports.create') }}"
                           class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-indigo-50 hover:border-indigo-300 transition">
                            <span class="text-2xl">📂</span>
                            <div>
                                <p class="font-medium text-gray-800">CSV取込</p>
                                <p class="text-xs text-gray-500">自社マスタをインポート</p>
                            </div>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
