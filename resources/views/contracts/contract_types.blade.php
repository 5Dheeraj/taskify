@extends('layout')

@section('title')
    <?= get_label('contract_types', 'Contract types') ?>
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
                            <a href="{{ route('contracts.index') }}"><?= get_label('contracts', 'Contracts') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('contract_types', 'Contract types') ?>
                        </li>

                    </ol>
                </nav>
            </div>
            <div>
                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create_contract_type_modal"><button
                        type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title=" <?= get_label('create_contract_type', 'Create contract type') ?>"><i
                            class="bx bx-plus"></i></button></a>
                <a href="{{ route('contracts.index') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('contracts', 'Contracts') ?>"><i
                            class='bx bx-list-ul'></i></button></a>
            </div>
        </div>

        <div class="card ">
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    @if ($contract_types > 0)
                        <input type="hidden" id="data_type" value="contract-type">

                        <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                            data-url="{{ route('contracts.contract_types_list') }}" data-icons-prefix="bx"
                            data-icons="icons" data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParams" data-route-prefix="{{ Route::getCurrentRoute()->getPrefix() }}">

                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-sortable="true" data-field="id"><?= get_label('id', 'ID') ?></th>
                                    <th data-sortable="true" data-field="type"><?= get_label('type', 'Type') ?></th>
                                    <th data-formatter="actionsFormatter"><?= get_label('actions', 'Actions') ?></th>
                                </tr>
                            </thead>
                        </table>
                    @else
                        <?php
                        $type = 'Contract types'; ?>
                        <x-empty-state-card :type="$type" />
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        var label_update = '<?= get_label('update ', 'Update ') ?>';
        var label_delete = '<?= get_label('delete ', 'Delete ') ?>';
    </script>
    <script src="{{ asset('assets/js/pages/contract-types.js') }}"></script>
@endsection
