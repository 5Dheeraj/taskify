@extends('layout')
@section('title')
    {{ get_label('whatsapp_notifications_settings', 'Whatsapp Notifications Settings') }}
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
                            <?= get_label('whatsapp_notifications_settings', 'Whatsapp Notifications Settings') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="alert alert-dark fw-semibold text-capitalize" role="alert">
                    <?= get_label('important_settings_for_whatsapp_notification_feature_to_be_work', 'Important settings for WhatsApp notification feature to be work.') ?>
                    <a href="javascript:void(0)" data-bs-toggle="modal"
                        data-bs-target="#admin_whatsapp_instuction_modal"><?= get_label('click_for_help', 'Click here for help.') ?></a>
                </div>

                <form action="{{ route('admin_whatsapp_notifications_settings.update') }}" class="form-submit-event"
                    method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="redirect_url"
                        value="{{ route('admin_whatsapp_notifications_settings.index') }}">
                    @csrf
                    @method('PUT')
                    {{-- @dd($admin_whatsapp_settings) --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="api_key" class="form-label"><?= get_label('api_key', 'API Key') ?>
                                <span class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="api_key" name="api_key"
                                placeholder="Enter Your API Key" value="{{ $admin_whatsapp_settings['api_key'] ?? '' }}">

                            @error('api_key')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror

                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sender_mobile_number"
                                class="form-label"><?= get_label('sender_mobile_number', 'Sender Mobile Number') ?>
                                <span class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="sender_mobile_number" name="sender_mobile_number"
                                placeholder="Enter Sender Mobile Number"
                                value="{{ $admin_whatsapp_settings['sender_mobile_number'] ?? '' }}">

                            @error('sender_mobile_number')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>



                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2"
                                id="submit_btn"><?= get_label('update', 'Update') ?></button>
                            <button type="reset"
                                class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="admin_whatsapp_instuction_modal" tabindex="-1"
        aria-labelledby="adminWhatsappInstructionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg  modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminWhatsappInstructionLabel">
                        <?= get_label('how_to_get_api_key_from_taskway', 'How to Get API Key from Taskway') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?= get_label('close', 'Close') ?>"></button>
                </div>
                <div class="modal-body">
                    <ol class="ps-3">
                        <li><?= get_label('taskway_open_url', 'Open') ?> <a href="https://web.taskway.in"
                                target="_blank">https://web.taskway.in</a></li>
                        <li><?= get_label('taskway_login', 'Login using:') ?>
                            <ul>
                                <li><strong><?= get_label('username', 'Username') ?>:</strong>
                                    <?= get_label('your_taskway_username', 'Your Taskway Username') ?></li>
                                <li><strong><?= get_label('password', 'Password') ?>:</strong>
                                    <?= get_label('your_taskway_password', 'Your Taskway Password') ?></li>
                            </ul>
                        </li>
                        <li><?= get_label('go_to_settings', 'Navigate to the top-right corner and click on') ?>
                            <strong><?= get_label('settings', 'Settings') ?></strong></li>
                        <li><?= get_label('check_devices', 'Go to') ?>
                            <strong><?= get_label('devices', 'Devices') ?></strong> →
                            <?= get_label('check_mobile_attached', 'Check if your mobile number is attached') ?></li>
                        <li><?= get_label('copy_api_key', 'Copy the API Key shown in the Settings Menu') ?></li>
                        <li><?= get_label('paste_api_key_kiwano', 'Paste the key and number into the My WhatsApp Settings form in your admin panel') ?>
                        </li>
                        <li>Contact <b>Support@kiwano.in</b> for any issues or queries</li>
                    </ol>
                    <div class="alert alert-dark text-capitalize fw-semibold mt-4" role="alert">
                        If the device isn’t attached, first scan the QR code using Taskway’s Android App to link your number. Go to <a href="https://web.taskway.in" >web.taskway.in</a> to link your whatsapp number
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal"><?= get_label('close', 'Close') ?></button>
                </div>
            </div>
        </div>
    </div>
@endsection
