<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('users.create', ['stores' => $this->companyStores()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'company_id' => auth()->user()->company_id,
        ]);

        return redirect()->route('users.index')->with('status', 'ユーザーを作成しました。');
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'stores' => $this->companyStores()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'ユーザー情報を更新しました。');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('users.index')->with('status', 'ユーザーの状態を変更しました。');
    }

    private function companyStores()
    {
        return Store::where('company_id', auth()->user()->company_id)
            ->orderBy('store_name')
            ->get();
    }
}
