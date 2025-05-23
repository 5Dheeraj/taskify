@extends('layout')
@section('title')
    <?= get_label('tasks', 'Tasks') ?> - <?= get_label('calendar_view', 'Calendar View') ?>
@endsection
@section('content')
    @php
        $routePrefix = Route::getCurrentRoute()->getPrefix();
    @endphp
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-2 mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('home.index') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <?= get_label('tasks', 'Tasks') ?>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('calendar_view', 'Calendar View') ?>
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                @php
                    $taskDefaultView = getUserPreferences('tasks', 'default_view');
                @endphp
                @if ($taskDefaultView === 'tasks/calendar-view')
                    <span class="badge bg-primary"><?= get_label('default_view', 'Default View') ?></span>
                @else
                    <a href="javascript:void(0);"><span class="badge bg-secondary" id="set-default-view" data-type="tasks"
                            data-view="calendar-view"><?= get_label('set_as_default_view', 'Set as Default View') ?></span></a>
                @endif
            </div>
            @include('partials.tasks-views-buttons')
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div id="taskCalenderDiv"></div>
            </div>
        </div>
    </div>
@endsection

