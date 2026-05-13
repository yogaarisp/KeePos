@extends('layouts.app')
@section('title', 'Manajemen User')
@section('header-actions')
<a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus" style="width:16px;height:16px;"></i> Tambah User</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>User</th><th>Username</th><th>Email</th><th style="text-align:center">Role</th><th style="text-align:center">Status</th><th>Login Terakhir</th><th style="text-align:center">Aksi</th></tr></thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--accent),#fb923c);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0;">
                                {{ strtoupper(substr($user->full_name, 0, 1)) }}
                            </div>
                            <strong style="color:var(--text-primary);">{{ $user->full_name }}</strong>
                        </div>
                    </td>
                    <td>{{ $user->username }}</td>
                    <td style="font-size:13px;">{{ $user->email }}</td>
                    <td style="text-align:center"><span class="badge badge-{{ $user->role === 'admin' ? 'info' : 'warning' }}">{{ ucfirst($user->role) }}</span></td>
                    <td style="text-align:center"><span class="badge badge-{{ $user->is_active ? 'success' : 'danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $user->last_login ? $user->last_login->format('d/m/Y H:i') : 'Belum pernah' }}</td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center;">
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-secondary btn-icon"><i data-lucide="edit" style="width:14px;height:14px;"></i></a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" onsubmit="return confirm('Hapus user ini?')">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger btn-icon"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state">Belum ada user</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $users->links() }}</div>
@endsection
