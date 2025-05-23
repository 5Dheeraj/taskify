@extends('layout')

@section('title')
    <?= get_label('security_settings', 'Security Settings') ?>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('home.index') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <?= get_label('settings', 'Settings') ?>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('security_settings', 'Security Settings') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="card">
            <div class="card-body">

                <form action="{{ route('settings.security.store') }}" class="form-submit-event" method="POST">
                    <input type="hidden" name="dnr" >
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="max_login_attempts" class="form-label">
                                <?= get_label('max_login_attempts', 'Max Login Attempts') ?>
                                <span class="text-muted"> (<?= get_label('max_login_attempts_info', 'Leave it blank if you do not want to lock the account') ?>)</span>
                                <span data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Maximum number of login attempts before locking the account.">
                                    <i class="bx bxs-info-circle text-primary cursor-pointer" ></i>
                                </span>
                            </label>
                            <input class="form-control" min="0" type="number" name="max_login_attempts"
                                placeholder="Enter max login attempts"
                                value="{{ $security_settings['max_login_attempts']  }}">
                            @error('max_login_attempts')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="time_decay" class="form-label">
                                <?= get_label('time_decay', 'Time Decay') ?>
                                <span class="text-muted"> (<?= get_label('time_decay_info', 'This will not apply if login attempts are not locked') ?>)</span>
                                <span data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Time (in minutes) after which the login attempts are reset.">
                                    <i class="bx bxs-info-circle text-primary cursor-pointer"></i>
                                </span>
                            </label>
                            <input class="form-control" type="number" min="0" name="time_decay" placeholder="Enter time decay"
                                value="<?= config('constants.ALLOW_MODIFICATION') === 0 ? str_repeat('*', strlen($security_settings['time_decay'])) : $security_settings['time_decay'] ?>">
                            @error('time_decay')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2" id="submit_btn">
                                <?= get_label('update', 'Update') ?>
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <?= get_label('cancel', 'Cancel') ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
