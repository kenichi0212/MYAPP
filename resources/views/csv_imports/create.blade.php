<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CSVインポート
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('csv-imports.create') }}" enctype="multipart/form-data" x-data="{ scope: '{{ old('scope', 'all_stores') }}' }">
                    @csrf

                    <div>
                        <x-input-label for="file" value="CSVファイル" />
                        <input id="file" name="file" type="file" accept=".csv" class="mt-1 block w-full text-sm" required>
                        <x-input-error class="mt-2" :messages="$errors->get('file')" />
                    </div>

                    <div class="mt-6">
                        <x-input-label value="取込範囲" />
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center min-h-[44px]">
                                <input type="radio" name="scope" value="all_stores" x-model="scope" class="text-primary focus:ring-primary" checked>
                                <span class="ms-2">全店舗一本</span>
                            </label>
                            <label class="flex items-center min-h-[44px]">
                                <input type="radio" name="scope" value="store_group" x-model="scope" class="text-primary focus:ring-primary">
                                <span class="ms-2">店舗グループ単位</span>
                            </label>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('scope')" />
                    </div>

                    <div class="mt-4" x-show="scope === 'store_group'" x-cloak>
                        <x-input-label for="store_group_id" value="対象グループ" />
                        <select id="store_group_id" name="store_group_id" class="mt-1 block w-full border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm min-h-[44px]">
                            <option value="">未選択</option>
                            @foreach ($storeGroups as $group)
                                <option value="{{ $group->id }}" @selected(old('store_group_id') == $group->id)>
                                    {{ $group->group_name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('store_group_id')" />
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button>取込内容を確認する</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
