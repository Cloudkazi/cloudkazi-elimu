@extends('layouts.app')
@section('title', 'Payment Gateway & Bank Settings')
@section('breadcrumb', 'Finance › Payment Gateway Settings')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>⚙️ School Payment Gateway &amp; Banking Settings</h1>
        <p>Configure Safaricom M-Pesa Daraja APIs, Paybill, Till numbers, and multi-school bank accounts</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('finance.reconciliation') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left-right"></i> Reconciliation Center
        </a>
        <a href="{{ route('finance.payments') }}" class="btn btn-primary">
            <i class="bi bi-wallet2"></i> Bursar Payments
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="bi bi-check-circle-fill" style="font-size:1.2rem"></i>
    <div>{{ session('success') }}</div>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:20px">
    <ul style="margin:0;padding-left:20px">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;margin-bottom:30px">

    {{-- Left Column: M-Pesa & Payment Channels Configuration --}}
    <div>
        <form action="{{ route('finance.gateway-settings.update') }}" method="POST">
            @csrf

            {{-- 1. Safaricom Daraja M-Pesa Card --}}
            <div class="card" style="margin-bottom:24px">
                <div class="card-header" style="background:linear-gradient(135deg, #00A651, #007537);color:#fff;border-radius:10px 10px 0 0">
                    <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                        <h5 style="color:#fff;margin:0"><i class="bi bi-phone"></i> Safaricom Daraja M-Pesa Integration</h5>
                        <span class="badge" style="background:rgba(255,255,255,0.25);color:#fff;font-weight:700">
                            {{ strtoupper($settings->daraja_environment ?? 'sandbox') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:18px">
                        Configure Lipa Na M-Pesa credentials for automated STK Push, Paybill C2B settlement, and Till integration.
                    </p>

                    <div class="two-col" style="margin-bottom:14px">
                        <div class="form-group">
                            <label class="form-label">Environment <span style="color:red">*</span></label>
                            <select name="daraja_environment" class="form-select" required>
                                <option value="sandbox" {{ ($settings->daraja_environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                                    🧪 Sandbox (Simulation &amp; Testing)
                                </option>
                                <option value="production" {{ ($settings->daraja_environment ?? '') === 'production' ? 'selected' : '' }}>
                                    🚀 Production (Live Safaricom Network)
                                </option>
                            </select>
                            <small class="form-text">Sandbox allows instant zero-risk payment testing</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Business Shortcode / Paybill</label>
                            <input type="text" name="daraja_shortcode" class="form-control"
                                value="{{ old('daraja_shortcode', $settings->daraja_shortcode ?? '174379') }}"
                                placeholder="e.g. 174379">
                            <small class="form-text">Default Safaricom test shortcode is 174379</small>
                        </div>
                    </div>

                    <div class="two-col" style="margin-bottom:14px">
                        <div class="form-group">
                            <label class="form-label">Paybill Number (for Parent Direct Pay)</label>
                            <input type="text" name="daraja_paybill_number" class="form-control"
                                value="{{ old('daraja_paybill_number', $settings->daraja_paybill_number) }}"
                                placeholder="e.g. 522123">
                            <small class="form-text">Parents will enter this on M-Pesa menu</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Buy Goods Till Number</label>
                            <input type="text" name="daraja_till_number" class="form-control"
                                value="{{ old('daraja_till_number', $settings->daraja_till_number) }}"
                                placeholder="e.g. 987654">
                            <small class="form-text">For counter / cashier till collections</small>
                        </div>
                    </div>

                    <div class="two-col" style="margin-bottom:14px">
                        <div class="form-group">
                            <label class="form-label">Registered Business Name</label>
                            <input type="text" name="daraja_business_name" class="form-control"
                                value="{{ old('daraja_business_name', $settings->daraja_business_name) }}"
                                placeholder="e.g. CloudKazi Premier Academy">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Webhook Callback URL</label>
                            <input type="url" name="daraja_callback_url" class="form-control"
                                value="{{ old('daraja_callback_url', $settings->daraja_callback_url ?? route('mpesa.callback')) }}"
                                placeholder="https://yourdomain.com/mpesa/callback">
                            <small class="form-text">Auto-populated with this school's IPN endpoint</small>
                        </div>
                    </div>

                    <div style="background:var(--surface-subtle);border-radius:8px;padding:14px;margin-bottom:16px">
                        <h6 style="font-size:0.85rem;font-weight:700;margin-bottom:10px;color:var(--text)">
                            <i class="bi bi-key"></i> Safaricom Developer API Keys
                        </h6>
                        <div class="form-group" style="margin-bottom:12px">
                            <label class="form-label">Consumer Key</label>
                            <input type="text" name="daraja_consumer_key" class="form-control"
                                value="{{ old('daraja_consumer_key', $settings->daraja_consumer_key) }}"
                                placeholder="Enter Safaricom Consumer Key">
                        </div>
                        <div class="form-group" style="margin-bottom:12px">
                            <label class="form-label">Consumer Secret</label>
                            <input type="password" name="daraja_consumer_secret" class="form-control"
                                value="{{ old('daraja_consumer_secret', $settings->daraja_consumer_secret) }}"
                                placeholder="Enter Safaricom Consumer Secret">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Online Passkey (Lipa Na M-Pesa Online)</label>
                            <input type="password" name="daraja_passkey" class="form-control"
                                value="{{ old('daraja_passkey', $settings->daraja_passkey) }}"
                                placeholder="bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Enabled Payment Methods Selector --}}
            <div class="card" style="margin-bottom:24px">
                <div class="card-header">
                    <h5><i class="bi bi-toggles"></i> Active Payment Channels</h5>
                </div>
                <div class="card-body">
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:14px">
                        Select which payment channels are activated for parent portal and bursar recording.
                    </p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:12px">
                        @foreach($defaultMethods as $methodKey => $methodTitle)
                        @php
                            $isEnabled = $settings->isMethodEnabled($methodKey);
                        @endphp
                        <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--border);border-radius:8px;cursor:pointer;background:{{ $isEnabled ? 'var(--surface-subtle)' : '#fff' }}">
                            <input type="checkbox" name="enabled_methods[]" value="{{ $methodKey }}"
                                {{ $isEnabled ? 'checked' : '' }} style="width:18px;height:18px">
                            <div>
                                <div style="font-weight:600;font-size:0.9rem">{{ $methodTitle }}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">{{ $methodKey }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer" style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-save"></i> Save Gateway Configuration
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Right Column: Multi-School Bank Accounts Management --}}
    <div>
        <div class="card" style="margin-bottom:24px">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h5><i class="bi bi-bank"></i> School Bank Accounts</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('add-bank-modal').style.display='flex'">
                    <i class="bi bi-plus-circle"></i> Add Bank
                </button>
            </div>
            <div class="card-body" style="padding:0">
                @forelse($bankAccounts as $bank)
                <div style="padding:16px;border-bottom:1px solid var(--border);display:flex;flex-direction:column;gap:8px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div style="font-weight:700;font-size:1.05rem;color:var(--text)">
                                {{ $bank->bank_name }}
                                @if($bank->is_primary)
                                    <span class="badge badge-success" style="font-size:0.7rem;margin-left:6px">PRIMARY</span>
                                @endif
                                @if(!$bank->is_active)
                                    <span class="badge badge-gray" style="font-size:0.7rem;margin-left:6px">INACTIVE</span>
                                @endif
                            </div>
                            <div style="font-size:0.85rem;color:var(--text-muted)">
                                Account: <strong>{{ $bank->account_name }}</strong>
                            </div>
                            <div style="font-family:monospace;font-size:1rem;color:var(--primary);font-weight:700;margin-top:2px">
                                {{ $bank->account_number }}
                            </div>
                            @if($bank->branch)
                                <div style="font-size:0.8rem;color:var(--text-muted)">Branch: {{ $bank->branch }}</div>
                            @endif
                        </div>
                        <div style="display:flex;gap:6px">
                            @if(!$bank->is_primary)
                            <form action="{{ route('finance.bank-account.primary', $bank->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" title="Set as Primary">
                                    <i class="bi bi-star"></i>
                                </button>
                            </form>
                            @endif
                            <form action="{{ route('finance.bank-account.toggle', $bank->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" title="{{ $bank->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-power" style="color:{{ $bank->is_active ? '#00A651' : '#94a3b8' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('finance.bank-account.destroy', $bank->id) }}" method="POST" onsubmit="return confirm('Delete this bank account?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div style="padding:30px;text-align:center;color:var(--text-muted)">
                    <div style="font-size:2rem;margin-bottom:8px">🏛️</div>
                    <p style="margin:0">No bank accounts registered yet.</p>
                    <small>Add school bank accounts so parents can do direct bank transfers and deposits.</small>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Guide Card --}}
        <div class="card" style="background:var(--surface-subtle)">
            <div class="card-body">
                <h6 style="font-weight:700;margin-bottom:10px"><i class="bi bi-info-circle"></i> Multi-School Gateway Architecture</h6>
                <ul style="font-size:0.82rem;color:var(--text-muted);padding-left:18px;margin:0;line-height:1.6">
                    <li>Each school operates with its own isolated credentials and bank accounts.</li>
                    <li>Safaricom IPN callbacks automatically route to the corresponding student invoice via <code>school_id</code>.</li>
                    <li>Parent portal instantly displays active accounts and live M-Pesa STK push.</li>
                    <li>Payments automatically update fee balances, student statements, and bursar ledger.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Add Bank Account Modal --}}
<div id="add-bank-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;width:95%;max-width:540px;box-shadow:0 10px 25px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:var(--primary);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
            <h5 style="margin:0;color:#fff"><i class="bi bi-bank"></i> Add School Bank Account</h5>
            <button type="button" onclick="document.getElementById('add-bank-modal').style.display='none'" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('finance.bank-account.store') }}" method="POST">
            @csrf
            <div style="padding:20px">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Bank Name <span style="color:red">*</span></label>
                    <input type="text" name="bank_name" class="form-control" required
                        placeholder="e.g. Kenya Commercial Bank (KCB), Equity Bank, Co-operative Bank">
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Account Name <span style="color:red">*</span></label>
                    <input type="text" name="account_name" class="form-control" required
                        placeholder="e.g. CloudKazi Academy Operations A/C">
                </div>
                <div class="two-col" style="margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Account Number <span style="color:red">*</span></label>
                        <input type="text" name="account_number" class="form-control" required
                            placeholder="e.g. 1234567890">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch</label>
                        <input type="text" name="branch" class="form-control"
                            placeholder="e.g. Westlands Branch">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">SWIFT / Sort Code</label>
                    <input type="text" name="bank_code" class="form-control"
                        placeholder="e.g. KCBLKENX">
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Instructions / Notes for Parents</label>
                    <input type="text" name="notes" class="form-control"
                        placeholder="e.g. Quote student admission number on the deposit slip">
                </div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_primary" value="1" style="width:16px;height:16px">
                    <span style="font-size:0.9rem;font-weight:600">Set as Primary School Bank Account</span>
                </label>
            </div>
            <div style="background:var(--surface-subtle);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('add-bank-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Account</button>
            </div>
        </form>
    </div>
</div>
@endsection
