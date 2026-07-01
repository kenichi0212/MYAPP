<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">商品一覧</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- フィルタフォーム --}}
            <form method="GET" action="{{ route('check-logs.index') }}"
                  class="bg-white shadow-sm rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-3 items-end">

                <div>
                    <label class="block text-xs text-gray-500 mb-1">事業所</label>
                    <select name="office_name" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm text-sm min-h-[44px]">
                        <option value="">すべての事業所</option>
                        @foreach ($officeNames as $officeName)
                            <option value="{{ $officeName }}" @selected(request('office_name') === $officeName)>
                                {{ $officeName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">店舗</label>
                    <select name="store_id" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm text-sm min-h-[44px]">
                        <option value="">すべての店舗</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store_id') == $store->id)>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">担当者</label>
                    <select name="checked_by" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm text-sm min-h-[44px]">
                        <option value="">すべての担当者</option>
                        @foreach ($checkers as $checker)
                            <option value="{{ $checker->id }}" @selected(request('checked_by') == $checker->id)>
                                {{ $checker->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">賞味期限</label>
                    <select name="expiry_within" class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm text-sm min-h-[44px]">
                        <option value="">期限を絞らない</option>
                        <option value="1"  @selected(request('expiry_within') == '1')>1ヶ月未満</option>
                        <option value="2"  @selected(request('expiry_within') == '2')>2ヶ月未満</option>
                        <option value="3"  @selected(request('expiry_within') == '3')>3ヶ月未満</option>
                        <option value="6"  @selected(request('expiry_within') == '6')>6ヶ月未満</option>
                    </select>
                </div>

                <div class="flex items-center gap-1 min-h-[44px]">
                    <input type="checkbox" id="needs_attention_only" name="needs_attention_only" value="1"
                           @checked(request('needs_attention_only'))
                           class="rounded border-gray-300 text-danger w-5 h-5">
                    <label for="needs_attention_only" class="text-sm text-gray-700">要確認のみ</label>
                </div>

                <div class="flex items-center gap-1 min-h-[44px]">
                    <input type="checkbox" id="show_processed" name="show_processed" value="1"
                           @checked(request('show_processed'))
                           class="rounded border-gray-300 text-primary w-5 h-5">
                    <label for="show_processed" class="text-sm text-gray-700">処理済みを含む</label>
                </div>

                <div class="flex gap-2 col-span-full lg:col-auto">
                    <x-primary-button type="submit">絞り込む</x-primary-button>
                    <a href="{{ route('check-logs.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 flex items-center">
                        リセット
                    </a>
                </div>
            </form>

            {{-- 件数表示 --}}
            <p class="text-sm text-gray-500 mb-3">
                {{ $logs->total() }} 件中 {{ $logs->firstItem() ?? 0 }}〜{{ $logs->lastItem() ?? 0 }} 件を表示
            </p>

            {{-- 一覧テーブル --}}
            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">商品名</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">JANコード</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">賞味期限</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">数量</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">店舗</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">担当者</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">登録日時</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">状態</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">処理</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            @php
                                $daysLeft = now()->diffInDays($log->expiry_date, false);
                                $rowClass = match (true) {
                                    $daysLeft < 0   => 'bg-danger/5',
                                    $daysLeft < 31  => 'bg-danger/5',
                                    $daysLeft < 91  => 'bg-warning/5',
                                    default         => '',
                                };
                                $expiryClass = match (true) {
                                    $daysLeft < 0   => 'text-danger font-semibold',
                                    $daysLeft < 31  => 'text-danger font-semibold',
                                    $daysLeft < 91  => 'text-warning font-semibold',
                                    default         => 'text-gray-800',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $log->product?->product_name ?? '—' }}
                                    @if ($log->product?->maker_name)
                                        <span class="text-xs text-gray-400 block">{{ $log->product->maker_name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                                    {{ $log->product?->jan_code ?? '—' }}
                                </td>
                                <td class="px-4 py-3 {{ $expiryClass }}">
                                    {{ $log->expiry_date->format('Y/m/d') }}
                                    @if ($daysLeft >= 0)
                                        <span class="text-xs text-gray-400 block">あと{{ $daysLeft }}日</span>
                                    @else
                                        <span class="text-xs text-danger block">期限切れ</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-800">
                                    @if ($log->is_zero_report)
                                        <span class="text-xs text-gray-500">ゼロ登録</span>
                                    @else
                                        {{ $log->quantity }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $log->store?->store_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $log->checkedBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $log->checked_at->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($log->processed_at)
                                        <span class="inline-block px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500 rounded-full">
                                            処理済み
                                        </span>
                                    @elseif ($log->needs_attention)
                                        <span class="inline-block px-2 py-0.5 text-xs font-semibold bg-danger/10 text-danger rounded-full">
                                            要確認
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if (! $log->processed_at)
                                        <button type="button"
                                            data-log-id="{{ $log->id }}"
                                            data-dispose-url="{{ route('api.check-logs.dispose', $log) }}"
                                            data-process-url="{{ route('api.check-logs.process', $log) }}"
                                            class="dispose-btn text-xs text-gray-500 underline hover:text-danger">
                                            処理する
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300">{{ $log->processed_at->format('m/d') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                    登録データがありません
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ページネーション --}}
            @if ($logs->hasPages())
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif

        </div>
    </div>

    {{-- 処分登録モーダル --}}
    <div id="dispose-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">処理内容を登録</h3>
            <div class="mb-3">
                <label class="block text-xs text-gray-500 mb-1">処理種別 <span class="text-danger">*</span></label>
                <select id="modal-process-type" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">選択してください</option>
                    <option value="disposal">廃棄</option>
                    <option value="discount">値引き</option>
                    <option value="return">返品</option>
                    <option value="other">その他（売り切れ等）</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-xs text-gray-500 mb-1">処理数量 <span class="text-danger">*</span></label>
                <input id="modal-quantity" type="number" min="1" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="例：5">
            </div>
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">備考（任意）</label>
                <input id="modal-note" type="text" maxlength="500" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="例：2026/07/01 廃棄処分">
            </div>
            <div class="flex gap-2 justify-end">
                <button id="modal-cancel" type="button"
                    class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200">
                    キャンセル
                </button>
                <button id="modal-submit" type="button"
                    class="px-4 py-2 text-sm text-white bg-danger rounded-md hover:bg-danger/80">
                    登録する
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const modal      = document.getElementById('dispose-modal');
    const typeSelect = document.getElementById('modal-process-type');
    const qtyInput   = document.getElementById('modal-quantity');
    const noteInput  = document.getElementById('modal-note');
    const cancelBtn  = document.getElementById('modal-cancel');
    const submitBtn  = document.getElementById('modal-submit');
    const csrf       = document.querySelector('meta[name="csrf-token"]').content;

    let currentBtn = null;

    document.querySelectorAll('.dispose-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentBtn = btn;
            typeSelect.value = '';
            qtyInput.value = '';
            noteInput.value = '';
            modal.classList.remove('hidden');
        });
    });

    cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
    modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });

    submitBtn.addEventListener('click', async () => {
        if (! typeSelect.value) { alert('処理種別を選択してください。'); return; }
        if (! qtyInput.value || Number(qtyInput.value) < 1) { alert('処理数量を1以上で入力してください。'); return; }

        submitBtn.disabled = true;
        submitBtn.textContent = '登録中…';

        try {
            const res = await fetch(currentBtn.dataset.disposeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    process_type: typeSelect.value,
                    quantity: Number(qtyInput.value),
                    note: noteInput.value || null,
                }),
            });

            if (res.ok) {
                modal.classList.add('hidden');
                const row = currentBtn.closest('tr');
                row.style.transition = 'opacity 0.4s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 400);
            } else {
                const body = await res.json().catch(() => ({}));
                alert(body.message ?? '登録に失敗しました。再度お試しください。');
            }
        } catch {
            alert('通信エラーが発生しました。');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = '登録する';
        }
    });
    </script>
    @endpush
</x-app-layout>
